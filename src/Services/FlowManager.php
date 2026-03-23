<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Contracts\Workable;
use Callcocam\LaravelRaptorFlow\Enums\FlowAction;
use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowParticipant;
use Callcocam\LaravelRaptorFlow\Models\FlowPreset;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Callcocam\LaravelRaptorFlow\Support\Builders\FlowPresetStepPayloadBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * API principal do pacote. Etapas (FlowConfigStep) ligadas ao configurável (ex.: Planograma);
 * execuções (FlowExecution) por workable (ex.: Gôndola). Sem FlowConfig: Flow → FlowStepTemplate,
 * configurável → FlowConfigStep → FlowStepTemplate, FlowExecution → FlowConfigStep.
 */
class FlowManager
{
    public function __construct(
        public ?SyncDefaultStepParticipantsService $syncDefaultStepParticipantsService = null,
        public ?FlowPresetStepPayloadBuilder $flowPresetStepPayloadBuilder = null,
        public ?RecordFlowHistoryService $recordFlowHistoryService = null,
        public ?RecordFlowMetricService $recordFlowMetricService = null,
        public ?FlowNotificationService $flowNotificationService = null,
    ) {}

    protected function syncDefaultStepParticipantsService(): SyncDefaultStepParticipantsService
    {
        return $this->syncDefaultStepParticipantsService
            ??= app(SyncDefaultStepParticipantsService::class);
    }

    protected function flowPresetStepPayloadBuilder(): FlowPresetStepPayloadBuilder
    {
        return $this->flowPresetStepPayloadBuilder
            ??= app(FlowPresetStepPayloadBuilder::class);
    }

    protected function recordFlowHistoryService(): RecordFlowHistoryService
    {
        return $this->recordFlowHistoryService
            ??= app(RecordFlowHistoryService::class);
    }

    protected function recordFlowMetricService(): RecordFlowMetricService
    {
        return $this->recordFlowMetricService
            ??= app(RecordFlowMetricService::class);
    }

    protected function flowNotificationService(): FlowNotificationService
    {
        return $this->flowNotificationService
            ??= app(FlowNotificationService::class);
    }

    /**
     * Aplica um preset: cria FlowConfigSteps para o configurável a partir do preset.
     *
     * @return Collection<int, FlowConfigStep>
     */
    public function applyPreset(Workable $configurable, string $presetSlug): Collection
    {
        $preset = FlowPreset::where('slug', $presetSlug)->where('is_active', true)->firstOrFail();
        $preset->load('steps.stepTemplate', 'steps.participants');

        $order = 1;
        $steps = [];
        foreach ($preset->steps as $presetStep) {
            $payload = $this->flowPresetStepPayloadBuilder()->buildFromPresetStep($presetStep, $order++);

            $step = FlowConfigStep::create(array_merge([
                'configurable_type' => get_class($configurable),
                'configurable_id' => $configurable->getWorkflowKey(),
            ], Arr::except($payload, ['users'])));

            $this->syncDefaultStepParticipantsService()->syncForStep($step, $payload['users'] ?? []);

            $steps[] = $step;
        }

        return collect($steps);
    }

    /**
     * Cria etapas (FlowConfigStep) para o configurável a partir do array de steps.
     *
    * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null, users?: array<int, string>}>  $steps
     * @return Collection<int, FlowConfigStep>
     */
    public function createStepsFor(Workable $configurable, array $steps, ?string $name = null, ?string $description = null): Collection
    {
        $this->upsertStepsFor($configurable, $steps);

        return $this->getStepsFor($configurable);
    }

    /**
     * Sincroniza as etapas do configurável com o array enviado (create/update/remove).
     *
     * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null, users?: array<int, string>}>  $steps
     */
    public function syncStepsFor(Workable $configurable, array $steps): void
    {
        $incomingTemplateIds = collect($steps)->pluck('flow_step_template_id')->filter()->values()->toArray();

        $stepsToDelete = FlowConfigStep::where('configurable_type', get_class($configurable))
            ->where('configurable_id', $configurable->getWorkflowKey())
            ->whereNotIn('flow_step_template_id', $incomingTemplateIds)
            ->get(['id']);

        if ($stepsToDelete->isNotEmpty()) {
            FlowParticipant::where('participable_type', FlowConfigStep::class)
                ->whereIn('participable_id', $stepsToDelete->pluck('id')->all())
                ->delete();
        }

        FlowConfigStep::where('configurable_type', get_class($configurable))
            ->where('configurable_id', $configurable->getWorkflowKey())
            ->whereNotIn('flow_step_template_id', $incomingTemplateIds)
            ->delete();

        $this->upsertStepsFor($configurable, $steps);
    }

    /**
     * @param  array<int, array{flow_step_template_id: string, order?: int, default_role_id?: string|null, estimated_duration_days?: int|null, suggested_responsible_id?: string|null, users?: array<int, string>}>  $steps
     */
    protected function upsertStepsFor(Workable $configurable, array $steps): void
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
            $configStep = FlowConfigStep::updateOrCreate(
                [
                    'configurable_type' => get_class($configurable),
                    'configurable_id' => $configurable->getWorkflowKey(),
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

            $this->syncDefaultStepParticipantsService()->syncForStep($configStep, $stepData['users'] ?? []);
        }
    }

    /**
     * Retorna as etapas (FlowConfigStep) do configurável, ordenadas.
     *
     * @return Collection<int, FlowConfigStep>
     */
    public function getStepsFor(Workable $configurable): Collection
    {
        return FlowConfigStep::where('configurable_type', get_class($configurable))
            ->where('configurable_id', $configurable->getWorkflowKey())
            ->with(['stepTemplate', 'participants'])
            ->orderBy('order')
            ->get();
    }

    /**
     * Inicia a execução do workflow para um workable na primeira etapa do configurável.
     *
     * @param  string|int  $startedByUserId
     */
    public function startExecution(Workable $workable, Workable $configurable, string|int $startedByUserId): FlowExecution
    {
        $steps = $this->getStepsFor($configurable);
        $firstStep = $steps->where('is_active', true)->sortBy('order')->first();
        if (! $firstStep) {
            throw ValidationException::withMessages([
                'config' => 'O configurável não possui etapas ativas.',
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

        $this->recordFlowHistoryService()->record($execution, FlowAction::Start, [
            'flow_config_step_id' => $firstStep->id,
            'user_id' => $startedByUserId,
            'notes' => 'Workflow iniciado manualmente',
        ]);

        $this->flowNotificationService()->notifyAssigned(
            $execution,
            $startedByUserId,
            'Workflow iniciado',
            'Você iniciou e assumiu esta execução do workflow.',
        );

        return $execution->load(['configStep', 'stepTemplate']);
    }

    /**
     * Cria uma execução pendente na primeira etapa do configurável.
     */
    public function createPendingExecution(Workable $workable, Workable $configurable): FlowExecution
    {
        $steps = $this->getStepsFor($configurable);
        $firstStep = $steps->where('is_active', true)->sortBy('order')->first();
        if (! $firstStep) {
            throw ValidationException::withMessages([
                'config' => 'O configurável não possui etapas ativas.',
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
     * Move uma execução para outra etapa do mesmo configurável.
     *
     * @param  string|int|null  $movedByUserId
     */
    public function moveExecution(FlowExecution $execution, FlowConfigStep $toStep, string|int|null $movedByUserId = null, ?string $notes = null): FlowExecution
    {
        $transitionedAt = now();

        $execution->load('configStep');
        $fromStep = $execution->configStep;
        if (! $fromStep) {
            throw ValidationException::withMessages([
                'execution' => 'Execução sem etapa configurada.',
            ]);
        }

        $sameConfigurable = $fromStep->configurable_type === $toStep->configurable_type
            && $fromStep->configurable_id === $toStep->configurable_id;
        if (! $sameConfigurable) {
            throw ValidationException::withMessages([
                'to_step' => 'A etapa de destino não pertence ao mesmo configurável do workflow.',
            ]);
        }

        if (! $toStep->is_active) {
            throw ValidationException::withMessages([
                'to_step' => 'A etapa de destino não está ativa.',
            ]);
        }

        $this->validateCanMoveToStep($fromStep, $toStep);

        $durationMinutes = null;
        if ($execution->started_at) {
            $durationMinutes = (int) $transitionedAt->diffInMinutes($execution->started_at);
        }
        $slaAtTransition = $execution->sla_date;
        $wasOverdue = $execution->sla_date && $transitionedAt->isAfter($execution->sla_date);
        $previousResponsibleId = $execution->current_responsible_id;
        $snapshotBefore = $execution->only([
            'flow_config_step_id', 'flow_step_template_id', 'status', 'current_responsible_id',
            'started_at', 'sla_date', 'estimated_duration_days',
        ]);

        $estimatedDays = (int) ($toStep->estimated_duration_days ?? 2);
        $newSlaDate = $estimatedDays > 0 ? $transitionedAt->copy()->addDays($estimatedDays) : null;

        $this->recordFlowMetricService()->recordStepTransitionMetric(
            $execution,
            $fromStep,
            $toStep,
            $transitionedAt,
        );

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

        $this->recordFlowHistoryService()->record($execution, FlowAction::Move, [
            'flow_config_step_id' => $toStep->id,
            'from_step_id' => $fromStep->id,
            'to_step_id' => $toStep->id,
            'user_id' => $movedByUserId,
            'previous_responsible_id' => $previousResponsibleId,
            'duration_in_step_minutes' => $durationMinutes,
            'sla_at_transition' => $slaAtTransition,
            'was_overdue' => $wasOverdue,
            'notes' => $notes,
            'snapshot' => $snapshotBefore,
        ]);

        $this->flowNotificationService()->notifyMoved(
            $execution,
            $movedByUserId,
            $fromStep,
            $toStep,
        );

        $this->flowNotificationService()->notifyAssigned(
            $execution,
            $toStep->suggested_responsible_id,
            'Nova etapa disponível',
            'Uma execução foi movida para sua etapa sugerida no workflow.',
            metadata: [
                'from_step_id' => (string) $fromStep->id,
                'to_step_id' => (string) $toStep->id,
            ],
        );

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    protected function validateCanMoveToStep(FlowConfigStep $fromStep, FlowConfigStep $toStep): void
    {
        $fromOrder = (int) $fromStep->order;
        $toOrder = (int) $toStep->order;
        if ($toOrder <= $fromOrder) {
            return;
        }

        $stepsBetween = FlowConfigStep::where('configurable_type', $fromStep->configurable_type)
            ->where('configurable_id', $fromStep->configurable_id)
            ->where('is_active', true)
            ->where('order', '>', $fromOrder)
            ->where('order', '<', $toOrder)
            ->get();

        foreach ($stepsBetween as $step) {
            if ($step->is_required && ! $step->allow_skip) {
                throw ValidationException::withMessages([
                    'to_step' => 'Não é possível pular a etapa obrigatória: '.($step->name ?? 'Etapa').'.',
                ]);
            }
        }
    }

    /**
     * Inicia uma execução que está Pendente (transição para Em andamento).
     *
     * @param  string|int  $startedByUserId
     */
    public function startPendingExecution(FlowExecution $execution, string|int $startedByUserId): FlowExecution
    {
        if ($execution->status !== FlowStatus::Pending) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções pendentes podem ser iniciadas.',
            ]);
        }

        $execution->load('configStep');
        $firstStep = $execution->configStep;
        if (! $firstStep) {
            throw ValidationException::withMessages([
                'execution' => 'Execução sem etapa configurada.',
            ]);
        }

        $this->ensureUserMatchesStepDefaultRole($execution, $startedByUserId);

        $estimatedDays = (int) ($execution->estimated_duration_days ?? 2);
        $slaDate = $estimatedDays > 0 ? now()->addDays($estimatedDays) : null;

        $execution->update([
            'status' => FlowStatus::InProgress,
            'current_responsible_id' => $startedByUserId,
            'execution_started_by' => $startedByUserId,
            'started_at' => now(),
            'sla_date' => $slaDate,
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Start, [
            'flow_config_step_id' => $firstStep->id,
            'user_id' => $startedByUserId,
            'notes' => 'Workflow iniciado manualmente',
        ]);

        $this->flowNotificationService()->notifyAssigned(
            $execution,
            $startedByUserId,
            'Execução iniciada',
            'Você assumiu uma execução pendente do workflow.',
        );

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    public function pauseExecution(FlowExecution $execution, string|int|null $pausedByUserId = null): FlowExecution
    {
        if ($execution->status !== FlowStatus::InProgress) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções em andamento podem ser pausadas.',
            ]);
        }

        $execution->update([
            'status' => FlowStatus::Paused,
            'paused_at' => now(),
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Pause, [
            'user_id' => $pausedByUserId,
        ]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    public function resumeExecution(FlowExecution $execution, string|int|null $resumedByUserId = null): FlowExecution
    {
        if ($execution->status !== FlowStatus::Paused || ! $execution->paused_at) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções pausadas podem ser retomadas.',
            ]);
        }

        $pausedMinutes = (int) now()->diffInMinutes($execution->paused_at);
        $totalPaused = (int) ($execution->paused_duration_minutes ?? 0) + $pausedMinutes;

        $execution->update([
            'status' => FlowStatus::InProgress,
            'paused_at' => null,
            'paused_duration_minutes' => $totalPaused,
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Resume, [
            'user_id' => $resumedByUserId,
        ]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    public function assignExecution(FlowExecution $execution, string|int $assignedByUserId, string|int $assignedToUserId, ?string $notes = null): FlowExecution
    {
        if (! in_array($execution->status, [FlowStatus::Pending, FlowStatus::InProgress], true)) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções pendentes ou em andamento podem ser reatribuídas.',
            ]);
        }

        $this->ensureUserMatchesStepDefaultRole($execution, $assignedToUserId);

        $previousResponsibleId = $execution->current_responsible_id;

        $execution->update([
            'current_responsible_id' => $assignedToUserId,
            'execution_started_by' => $execution->execution_started_by ?? $assignedToUserId,
            'status' => FlowStatus::InProgress,
            'started_at' => $execution->started_at ?? now(),
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Reassign, [
            'user_id' => $assignedByUserId,
            'previous_responsible_id' => $previousResponsibleId,
            'new_responsible_id' => $assignedToUserId,
            'notes' => $notes,
        ]);

        $this->flowNotificationService()->notifyAssigned(
            $execution,
            $assignedToUserId,
            'Responsabilidade atribuída',
            'Você foi designado como responsável desta execução de workflow.',
            metadata: [
                'assigned_by' => (string) $assignedByUserId,
                'previous_responsible_id' => $previousResponsibleId ? (string) $previousResponsibleId : null,
            ],
        );

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    public function abandonExecution(FlowExecution $execution, string|int $abandonedByUserId): FlowExecution
    {
        if ($execution->status !== FlowStatus::InProgress) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções em andamento podem ter responsabilidade abandonada.',
            ]);
        }

        if ($execution->current_responsible_id != $abandonedByUserId) {
            throw ValidationException::withMessages([
                'user_id' => 'Apenas o responsável atual pode abandonar a etapa.',
            ]);
        }

        $previousResponsibleId = $execution->current_responsible_id;

        $execution->update([
            'current_responsible_id' => null,
            'status' => FlowStatus::Pending,
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Abandon, [
            'user_id' => $abandonedByUserId,
            'previous_responsible_id' => $previousResponsibleId,
        ]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    /**
     * Finaliza uma execução em andamento (transição para Concluído).
     */
    public function finishExecution(FlowExecution $execution, string|int $finishedByUserId): FlowExecution
    {
        if ($execution->status !== FlowStatus::InProgress) {
            throw ValidationException::withMessages([
                'execution' => 'Apenas execuções em andamento podem ser finalizadas.',
            ]);
        }

        if ($execution->current_responsible_id != $finishedByUserId) {
            throw ValidationException::withMessages([
                'user_id' => 'Apenas o responsável atual pode finalizar a execução.',
            ]);
        }

        $execution->loadMissing('configStep');
        $currentStep = $execution->configStep;

        if (! $currentStep || $currentStep->order === null) {
            throw ValidationException::withMessages([
                'execution' => 'Não foi possível determinar a etapa atual da execução.',
            ]);
        }

        $hasNextStep = FlowConfigStep::query()
            ->where('configurable_type', $currentStep->configurable_type)
            ->where('configurable_id', $currentStep->configurable_id)
            ->where('is_active', true)
            ->where('order', '>', (int) $currentStep->order)
            ->exists();

        if ($hasNextStep) {
            throw ValidationException::withMessages([
                'execution' => 'A execução só pode ser finalizada na última etapa do workflow.',
            ]);
        }

        $execution->update([
            'status' => FlowStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->recordFlowHistoryService()->record($execution, FlowAction::Complete, [
            'user_id' => $finishedByUserId,
        ]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    public function updateExecutionNotes(FlowExecution $execution, string $notes): FlowExecution
    {
        $execution->update(['notes' => $notes]);

        return $execution->fresh(['configStep', 'stepTemplate']);
    }

    protected function ensureUserMatchesStepDefaultRole(FlowExecution $execution, string|int $userId): void
    {
        $execution->loadMissing('configStep');

        $requiredRoleId = $execution->configStep?->default_role_id;
        if (! $requiredRoleId) {
            return;
        }

        $checkRole = config('flow.policy.check_role');
        if (! is_callable($checkRole)) {
            return;
        }

        $userModel = config('auth.providers.users.model');
        if (! is_string($userModel) || ! class_exists($userModel)) {
            throw ValidationException::withMessages([
                'user_id' => 'Não foi possível validar a role do responsável da etapa.',
            ]);
        }

        /** @var \Illuminate\Database\Eloquent\Model|null $user */
        $user = $userModel::query()->find($userId);
        if (! $user || ! (bool) $checkRole($user, $requiredRoleId)) {
            throw ValidationException::withMessages([
                'user_id' => 'O responsável selecionado deve possuir a role padrão da etapa.',
            ]);
        }
    }
}
