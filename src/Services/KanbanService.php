<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Closure;

/**
 * Serviço Kanban genérico do pacote.
 *
 * Combina dois padrões para máxima flexibilidade:
 *
 * 1. **Fluent setters** – configuração direta sem extensão:
 *      $service->setFlow($flow)->setWorkableType(MyWorkflow::class)->getBoardData();
 * 
 */
class KanbanService
{
    protected ?Flow $flow = null;

    protected array $filters = [];

    /** @var Closure(FlowExecution, FlowConfigStep, mixed): array<string, mixed>|null */
    protected ?Closure $permissionsResolver = null;

    // ─────────────────────────────────────────────────────────────────────────
    // Fluent setters
    // ─────────────────────────────────────────────────────────────────────────

    public function setFlow(Flow $flow): static
    {
        $this->flow = $flow;

        return $this;
    }

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Injeta um resolver de permissões específico do domínio.
     * O closure recebe (FlowExecution, FlowConfigStep, $config) e deve retornar
     * um array com as chaves de permissão desejadas.
     *
     * @param  Closure(FlowExecution, FlowConfigStep, mixed): array<string, mixed>  $resolver
     */
    public function setPermissionsResolver(Closure $resolver): static
    {
        $this->permissionsResolver = $resolver;

        return $this;
    }
 

    // ─────────────────────────────────────────────────────────────────────────
    // Ponto de entrada principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Monta o board no formato árvore:
     * Flow -> StepTemplates -> FlowConfigStep(configurable_type/configurable_id) -> configs -> execution.
     *
     * A relação `configs` do configurable é a fonte padrão das gôndolas/itens.
     * A execution é selecionada pela etapa correta (flow_step_template_id + flow_config_step_id).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBoardData(): array
    {
        if ($this->flow === null) {
            return [];
        }

        $this->flow->loadMissing(['stepTemplates' => fn($query) => $query->where('is_active', true)->orderBy('suggested_order')]);

        return $this->flow->stepTemplates
            ->where('is_active', true)
            ->sortBy('suggested_order')
            ->values()
            ->map(function (FlowStepTemplate $flowStepTemplate) {
                $flowStepTemplate->loadMissing('configSteps');

                $formattedConfigSteps = $flowStepTemplate->configSteps
                    ->sortBy('order')
                    ->values()
                    ->map(fn(FlowConfigStep $configStep) => $this->formatConfigStep($configStep, $flowStepTemplate))
                    ->filter(fn(array $configStep) => ! empty($configStep['configs']))
                    ->values();

                return [
                    'id' => $flowStepTemplate->id,
                    'name' => $flowStepTemplate->name,
                    'slug' => $flowStepTemplate->slug,
                    'description' => $flowStepTemplate->description,
                    'color' => $flowStepTemplate->color,
                    'suggested_order' => $flowStepTemplate->suggested_order,
                    'executions' => $formattedConfigSteps
                        ->flatMap(fn(array $configStep) => collect($configStep['configs'] ?? []))
                        ->pluck('execution')
                        ->filter(fn($execution) => is_array($execution))
                        ->values()
                        ->toArray(),
                    'configSteps' => $formattedConfigSteps->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * @return array{id: string|null, order: int|null, configurable_id: string|null, configurable_type: string|null, configurable_label: string|null, configs: array<int, array<string, mixed>>}
     */
    protected function formatConfigStep(FlowConfigStep $configStep, FlowStepTemplate $flowStepTemplate): array
    {
        $configurableType = $configStep->configurable_type;
        $configurableId = $configStep->configurable_id;

        $configModel = null;

        if (is_string($configurableType) && class_exists($configurableType) && $configurableId !== null) {
            $configModel = $configurableType::query()->find($configurableId);
        }

        $configurableLabel = null;

        if (is_object($configModel) && method_exists($configModel, 'getWorkflowLabel')) {
            $configurableLabel = $configModel->getWorkflowLabel();
        }

        $configs = collect();

        if (is_object($configModel) && method_exists($configModel, 'configs')) {
            if (method_exists($configModel, 'loadMissing')) {
                $configModel->loadMissing('configs');
            }

            $configs = collect($configModel->configs)->values();
        }

        return [
            'id' => $configStep->id,
            'order' => $configStep->order,
            'configurable_id' => $configurableId,
            'configurable_type' => $configurableType,
            'configurable_label' => $configurableLabel,
            'configs' => $configs
                ->map(fn($config) => $this->formatConfigItem($config, $configStep, $flowStepTemplate, $configurableLabel))
                ->filter(fn(array $configItem) => is_array($configItem['execution']))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @return array{id: string|null, name: string|null, execution: array<string, mixed>|null}
     */
    protected function formatConfigItem(
        mixed $config,
        FlowConfigStep $configStep,
        FlowStepTemplate $flowStepTemplate,
        ?string $configurableLabel
    ): array {
        $execution = $this->resolveExecutionForStep($config, $configStep, $flowStepTemplate);

        return [
            'id' => is_object($config) && method_exists($config, 'getKey') ? $config->getKey() : data_get($config, 'id'),
            'name' => data_get($config, 'name') ?? data_get($config, 'title'),
            'execution' => $execution
                ? $this->formatExecution($execution, $configStep, $config, $configurableLabel)
                : null,
        ];
    }

    /**
     * Compatibilidade com testes e extensões antigas.
     *
     * @return array{id: string|null, name: string|null, execution: array<string, mixed>|null}
     */
    protected function formatConfigItemForStep(mixed $config, FlowConfigStep $configStep): array
    {
        $flowStepTemplate = new FlowStepTemplate;
        $flowStepTemplate->id = (string) $configStep->flow_step_template_id;

        return $this->formatConfigItem(
            $config,
            $configStep,
            $flowStepTemplate,
            null
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatExecution(
        FlowExecution $execution,
        FlowConfigStep $configStep,
        mixed $config,
        ?string $configurableLabel
    ): array {
        $status = is_object($execution->status) && property_exists($execution->status, 'value')
            ? $execution->status->value
            : (string) $execution->status;

        $payload = [
            'id' => $execution->id,
            'flow_config_step_id' => $execution->flow_config_step_id,
            'workflow_step_template_id' => $execution->flow_step_template_id,
            'status' => $status,
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'sla_date' => $execution->sla_date?->toIso8601String(),
            'notes' => $execution->notes,
            'workable' => [
                'id' => is_object($config) && method_exists($config, 'getKey') ? $config->getKey() : data_get($config, 'id'),
                'name' => data_get($config, 'name') ?? data_get($config, 'title'),
                'group_id' => $configStep->configurable_id,
                'group_label' => $configurableLabel,
            ],
        ];

        $user = auth()->user();
 

        $payload['permissions'] = [
            'can_move' => $user->can('move', $execution),
            'can_perform_actions' => ($user->can('start', $execution) || $user->can('move', $execution) || $user->can('assign', $execution)),
            'can_start_execution' => $user->can('start', $execution),
            'can_edit_planogram' => $user->can('update', $config),
        ]; 
        return $payload;
    }

    protected function resolveExecutionForStep(mixed $config, FlowConfigStep $configStep, FlowStepTemplate $flowStepTemplate): ?FlowExecution
    {
        if (! is_object($config)) {
            return null;
        }

        if (method_exists($config, 'workflowExecutions')) {
            $match = collect($config->workflowExecutions)->first(function ($execution) use ($configStep, $flowStepTemplate) {
                return (string) $execution->flow_config_step_id === (string) $configStep->id
                    && (string) $execution->flow_step_template_id === (string) $flowStepTemplate->id;
            });

            if ($match instanceof FlowExecution) {
                return $match;
            }
        }

        if (method_exists($config, 'execution')) {
            $execution = data_get($config, 'execution');
            if (
                $execution instanceof FlowExecution
                && (string) $execution->flow_config_step_id === (string) $configStep->id
                && (string) $execution->flow_step_template_id === (string) $flowStepTemplate->id
            ) {
                return $execution;
            }
        }

        if (! method_exists($config, 'getKey')) {
            return null;
        }

        return FlowExecution::query()
            ->where('workable_type', get_class($config))
            ->where('workable_id', $config->getKey())
            ->where('flow_config_step_id', $configStep->id)
            ->where('flow_step_template_id', $flowStepTemplate->id)
            ->first();
    }

    public function getFilterOptionsData(): array
    {
        return [];
    }
}
