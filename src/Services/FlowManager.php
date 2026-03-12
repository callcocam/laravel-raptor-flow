<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Contracts\Workable;
use Callcocam\LaravelRaptorFlow\Enums\FlowAction;
use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Models\FlowConfig;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowHistory;
use Callcocam\LaravelRaptorFlow\Models\FlowPreset;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Illuminate\Validation\ValidationException;

/**
 * API principal do pacote. Criação e sincronização de configs (inline ou por preset),
 * e controle de execuções (start, move, complete, pause, resume, assign, abandon).
 *
 * @see docs/plan.md seção 6.3
 */
class FlowManager
{
    /**
     * Cria uma config a partir de um preset (CRUD de presets).
     * Cria FlowConfig + FlowConfigSteps e, opcionalmente, FlowExecutions por workable.
     */
    public function applyPreset(Workable $configurable, string $presetSlug): FlowConfig
    {
        $preset = FlowPreset::where('slug', $presetSlug)->where('is_active', true)->firstOrFail();
        $preset->load('steps.stepTemplate');

        $config = FlowConfig::create([
            'name' => $preset->name,
            'description' => $preset->description,
            'configurable_type' => get_class($configurable),
            'configurable_id' => $configurable->getWorkflowKey(),
            'workflow_preset_id' => $preset->id,
            'status' => FlowStatus::Active,
        ]);

        $order = 1;
        foreach ($preset->steps as $presetStep) {
            FlowConfigStep::create([
                'flow_config_id' => $config->id,
                'flow_step_template_id' => $presetStep->workflow_step_template_id,
                'name' => $presetStep->name ?? $presetStep->stepTemplate?->name,
                'description' => $presetStep->stepTemplate?->description,
                'order' => $order++,
                'default_role_id' => $presetStep->default_role_id,
                'suggested_responsible_id' => $presetStep->suggested_responsible_id,
                'estimated_duration_days' => $presetStep->estimated_duration_days ?? $presetStep->stepTemplate?->estimated_duration_days,
                'is_required' => $presetStep->is_required,
                'is_active' => true,
                'allow_skip' => $presetStep->allow_skip,
                'auto_assign_role' => $presetStep->auto_assign_role,
                'auto_assign_user' => $presetStep->auto_assign_user,
            ]);
        }

        return $config->load('steps');
    }

    /**
     * Cria config manualmente a partir de steps definidos no formulário do workable (configuração inline).
     * Cada item de $steps deve ter flow_step_template_id e order; opcional: default_role_id, estimated_duration_days, suggested_responsible_id.
     *
     * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null}>  $steps
     */
    public function createConfig(Workable $configurable, array $steps, ?string $name = null, ?string $description = null): FlowConfig
    {
        $config = FlowConfig::create([
            'name' => $name ?? 'Config '.class_basename($configurable),
            'description' => $description,
            'configurable_type' => get_class($configurable),
            'configurable_id' => $configurable->getWorkflowKey(),
            'workflow_preset_id' => null,
            'status' => FlowStatus::Active,
        ]);

        $this->upsertConfigSteps($config, $steps);

        return $config->load('steps');
    }

    /**
     * Sincroniza as etapas da config com o array enviado (create/update/remove).
     * Usado quando o usuário edita o workable e altera o repeater de etapas no form.
     *
     * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null}>  $steps
     */
    public function syncConfigSteps(FlowConfig $config, array $steps): void
    {
        $incomingTemplateIds = collect($steps)->pluck('flow_step_template_id')->filter()->values()->toArray();
        $config->steps()->whereNotIn('flow_step_template_id', $incomingTemplateIds)->delete();
        $this->upsertConfigSteps($config, $steps);
    }

    /**
     * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null}>  $steps
     */
    protected function upsertConfigSteps(FlowConfig $config, array $steps): void
    {
        $order = 1;
        foreach ($steps as $stepData) {
            $templateId = $stepData['flow_step_template_id'] ?? null;
            if (! $templateId) {
                continue;
            }
            $template = FlowStepTemplate::find($templateId);
            if (! $template) {
                continue;
            }
            FlowConfigStep::updateOrCreate(
                [
                    'flow_config_id' => $config->id,
                    'flow_step_template_id' => $templateId,
                ],
                [
                    'name' => $template->name,
                    'description' => $template->description,
                    'order' => $order++,
                    'default_role_id' => $stepData['default_role_id'] ?? $template->default_role_id,
                    'suggested_responsible_id' => $stepData['suggested_responsible_id'] ?? null,
                    'estimated_duration_days' => $stepData['estimated_duration_days'] ?? $template->estimated_duration_days ?? 2,
                    'is_required' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Retorna a config ativa do configurable (planograma, etc.), se existir.
     */
    public function getConfigFor(Workable $configurable): ?FlowConfig
    {
        return FlowConfig::where('configurable_type', get_class($configurable))
            ->where('configurable_id', $configurable->getWorkflowKey())
            ->whereIn('status', [FlowStatus::Active, FlowStatus::Draft])
            ->with('steps.stepTemplate')
            ->first();
    }

    /**
     * Inicia a execução do workflow para um workable na primeira etapa da config.
     * Cria a FlowExecution e marca como em andamento.
     *
     * @param  string|int  $startedByUserId  ID do usuário que está iniciando (será o responsável inicial)
     */
    public function startExecution(Workable $workable, FlowConfig $config, string|int $startedByUserId): FlowExecution
    {
        $firstStep = $config->steps()->where('is_active', true)->orderBy('order')->first();
        if (! $firstStep) {
            throw ValidationException::withMessages([
                'config' => 'A configuração do workflow não possui etapas ativas.',
            ]);
        }

        $existing = FlowExecution::where('workable_type', get_class($workable))
            ->where('workable_id', $workable->getWorkflowKey())
            ->whereIn('status', [FlowStatus::Pending, FlowStatus::InProgress])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'workable' => 'Já existe uma execução em andamento ou pendente para este item.',
            ]);
        }

        $estimatedDays = (int) ($firstStep->estimated_duration_days ?? 2);
        $slaDate = $estimatedDays > 0 ? now()->addDays($estimatedDays) : null;

        $execution = FlowExecution::create([
            'workable_type' => get_class($workable),
            'workable_id' => $workable->getWorkflowKey(),
            'flow_config_step_id' => $firstStep->id,
            'flow_step_template_id' => $firstStep->flow_step_template_id,
            'status' => FlowStatus::InProgress,
            'current_responsible_id' => $startedByUserId,
            'execution_started_by' => $startedByUserId,
            'started_at' => now(),
            'estimated_duration_days' => $estimatedDays,
            'sla_date' => $slaDate,
        ]);

        FlowHistory::create([
            'workable_type' => get_class($workable),
            'workable_id' => $workable->getWorkflowKey(),
            'flow_config_step_id' => $firstStep->id,
            'action' => FlowAction::Start,
            'user_id' => $startedByUserId,
            'performed_at' => now(),
            'notes' => 'Workflow iniciado manualmente',
        ]);

        return $execution->load(['configStep', 'stepTemplate']);
    }

    /**
     * Cria uma execução pendente na primeira etapa da config (para seed ou onboarding).
     * O workable aparece no Kanban na primeira coluna; ao clicar "Iniciar" chama startExecution.
     */
    public function createPendingExecution(Workable $workable, FlowConfig $config): FlowExecution
    {
        $firstStep = $config->steps()->where('is_active', true)->orderBy('order')->first();
        if (! $firstStep) {
            throw ValidationException::withMessages([
                'config' => 'A configuração do workflow não possui etapas ativas.',
            ]);
        }

        $existing = FlowExecution::where('workable_type', get_class($workable))
            ->where('workable_id', $workable->getWorkflowKey())
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'workable' => 'Já existe uma execução para este item.',
            ]);
        }

        $estimatedDays = (int) ($firstStep->estimated_duration_days ?? 2);

        return FlowExecution::create([
            'workable_type' => get_class($workable),
            'workable_id' => $workable->getWorkflowKey(),
            'flow_config_step_id' => $firstStep->id,
            'flow_step_template_id' => $firstStep->flow_step_template_id,
            'status' => FlowStatus::Pending,
            'current_responsible_id' => null,
            'execution_started_by' => null,
            'started_at' => null,
            'completed_at' => null,
            'sla_date' => null,
            'estimated_duration_days' => $estimatedDays,
        ])->load(['configStep', 'stepTemplate']);
    }

    /**
     * Move uma execução para outra etapa da mesma config.
     * Atualiza a execução (nova etapa, status Pending, responsável limpo) e registra no histórico.
     *
     * @param  string|int|null  $movedByUserId  ID do usuário que está movendo (opcional)
     */
    public function moveExecution(FlowExecution $execution, FlowConfigStep $toStep, string|int|null $movedByUserId = null, ?string $notes = null): FlowExecution
    {
        $execution->load('configStep.config');
        $fromStep = $execution->configStep;
        if (! $fromStep) {
            throw ValidationException::withMessages([
                'execution' => 'Execução sem etapa configurada.',
            ]);
        }

        $config = $fromStep->config;
        if (! $config || $toStep->flow_config_id !== $config->id) {
            throw ValidationException::withMessages([
                'to_step' => 'A etapa de destino não pertence à mesma configuração do workflow.',
            ]);
        }

        if (! $toStep->is_active) {
            throw ValidationException::withMessages([
                'to_step' => 'A etapa de destino não está ativa.',
            ]);
        }

        $durationMinutes = null;
        if ($execution->started_at) {
            $durationMinutes = (int) now()->diffInMinutes($execution->started_at);
        }
        $slaAtTransition = $execution->sla_date;
        $wasOverdue = $execution->sla_date && now()->isAfter($execution->sla_date);
        $previousResponsibleId = $execution->current_responsible_id;
        $snapshotBefore = $execution->only([
            'flow_config_step_id', 'flow_step_template_id', 'status', 'current_responsible_id',
            'started_at', 'sla_date', 'estimated_duration_days',
        ]);

        $estimatedDays = (int) ($toStep->estimated_duration_days ?? 2);
        $newSlaDate = $estimatedDays > 0 ? now()->addDays($estimatedDays) : null;

        $execution->update([
            'flow_config_step_id' => $toStep->id,
            'flow_step_template_id' => $toStep->flow_step_template_id,
            'status' => FlowStatus::Pending,
            'current_responsible_id' => null,
            'execution_started_by' => null,
            'started_at' => null,
            'sla_date' => $newSlaDate,
            'estimated_duration_days' => $estimatedDays,
            'paused_at' => null,
            'paused_duration_minutes' => 0,
        ]);

        FlowHistory::create([
            'workable_type' => $execution->workable_type,
            'workable_id' => $execution->workable_id,
            'flow_config_step_id' => $toStep->id,
            'action' => FlowAction::Move,
            'from_step_id' => $fromStep->id,
            'to_step_id' => $toStep->id,
            'user_id' => $movedByUserId,
            'previous_responsible_id' => $previousResponsibleId,
            'performed_at' => now(),
            'duration_in_step_minutes' => $durationMinutes,
            'sla_at_transition' => $slaAtTransition,
            'was_overdue' => $wasOverdue,
            'notes' => $notes,
            'snapshot' => $snapshotBefore,
        ]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }
}
