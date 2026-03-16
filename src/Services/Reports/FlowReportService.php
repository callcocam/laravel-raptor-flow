<?php

namespace Callcocam\LaravelRaptorFlow\Services\Reports;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportChart;
use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportPreset;
use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportPresetWithTables;
use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportTable;
use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowHistory;
use Callcocam\LaravelRaptorFlow\Models\FlowMetric;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Callcocam\LaravelRaptorFlow\Services\Reports\Tables\ResponsibleActivityTable;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FlowReportService
{
    /**
     * @var array<string, class-string<Model>>
     */
    protected array $models;

    /**
     * @var array<int, string|array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}>
     */
    protected array $chartDefinitions = [];

    /**
     * @var array<int, string|array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}>
     */
    protected array $tableDefinitions = [
        ResponsibleActivityTable::class,
    ];

    public function __construct()
    {
        $this->models = [
            'flow' => Flow::class,
            'step_template' => FlowStepTemplate::class,
            'config_step' => FlowConfigStep::class,
            'execution' => FlowExecution::class,
            'history' => FlowHistory::class,
            'metric' => FlowMetric::class,
            'user' => (string) config('auth.providers.users.model'),
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function setModel(string $key, string $modelClass): static
    {
        $this->models[$key] = $modelClass;

        return $this;
    }

    /**
     * @param  array<int, string|array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}>  $chartDefinitions
     */
    public function withCharts(array $chartDefinitions): static
    {
        $this->chartDefinitions = $chartDefinitions;

        return $this;
    }

    public function withPreset(FlowReportPreset|string $preset): static
    {
        $instance = is_string($preset) ? app($preset) : $preset;

        if (! $instance instanceof FlowReportPreset) {
            throw new InvalidArgumentException('Preset must implement FlowReportPreset.');
        }

        $this->withCharts($instance->charts());

        if ($instance instanceof FlowReportPresetWithTables) {
            $this->withTables($instance->tables());
        }

        return $this;
    }

    /**
     * @param  array<int, string|array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}>  $tableDefinitions
     */
    public function withTables(array $tableDefinitions): static
    {
        $this->tableDefinitions = $tableDefinitions;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array<string, mixed>
     */
    public function build(array $rawFilters = []): array
    {
        $filters = $this->sanitizeFilters($rawFilters);

        $context = new FlowReportContext($filters, $this->models);

        $chartDefinitions = $this->chartDefinitions;

        if ($chartDefinitions === []) {
            throw new InvalidArgumentException('No charts configured for flow report. Use withCharts().');
        }

        $charts = [];
        $tables = [];

        foreach ($chartDefinitions as $definition) {
            $resolved = $this->resolveChartDefinition($definition);
            $chart = app($resolved['chart']);

            if (! $chart instanceof FlowReportChart) {
                throw new InvalidArgumentException('Chart class must implement FlowReportChart.');
            }

            $charts[$chart::key()] = [
                'type' => $resolved['type'] ?? $chart::defaultType(),
                'label' => $resolved['label'] ?? $chart::label(),
                'data' => $chart->build($context, $resolved['options'] ?? []),
            ];
        }

        foreach ($this->tableDefinitions as $definition) {
            $resolved = $this->resolveTableDefinition($definition);
            $table = app($resolved['table']);

            if (! $table instanceof FlowReportTable) {
                throw new InvalidArgumentException('Table class must implement FlowReportTable.');
            }

            $tables[$table::key()] = [
                'label' => $resolved['label'] ?? $table::label(),
                'data' => $table->build($context, $resolved['options'] ?? []),
            ];
        }

        $summary = [
            'total_executions' => (int) $context->executionQuery()->count(),
            'total_metrics' => (int) $context->metricQuery()->count(),
            'total_history_events' => (int) $context->historyQuery()->count(),
        ];

        return [
            'summary' => $summary,
            'filters' => [
                'values' => $filters,
                'options' => [
                    'flows' => $context->flowOptions(),
                    'responsibles' => $context->responsibleOptions(),
                ],
            ],
            'charts' => $charts,
            'tables' => $tables,
        ];
    }

    /**
     * @param  string|array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}  $definition
     * @return array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}
     */
    protected function resolveChartDefinition(string|array $definition): array
    {
        if (is_string($definition)) {
            return ['chart' => $definition];
        }

        return $definition;
    }

    /**
     * @param  string|array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}  $definition
     * @return array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}
     */
    protected function resolveTableDefinition(string|array $definition): array
    {
        if (is_string($definition)) {
            return ['table' => $definition];
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array<string, string|null>
     */
    protected function sanitizeFilters(array $rawFilters): array
    {
        return [
            'flow_slug' => $this->sanitizeNullableString($rawFilters['flow_slug'] ?? null),
            'date_from' => $this->sanitizeNullableString($rawFilters['date_from'] ?? null),
            'date_to' => $this->sanitizeNullableString($rawFilters['date_to'] ?? null),
            'responsible_id' => $this->sanitizeNullableString($rawFilters['responsible_id'] ?? null),
        ];
    }

    protected function sanitizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

}
