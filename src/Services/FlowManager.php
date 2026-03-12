<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Contracts\Workable;
use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Models\FlowConfig;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowPreset;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;

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
}
