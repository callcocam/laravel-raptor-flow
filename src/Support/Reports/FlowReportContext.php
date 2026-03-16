<?php

namespace Callcocam\LaravelRaptorFlow\Support\Reports;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowHistory;
use Callcocam\LaravelRaptorFlow\Models\FlowMetric;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FlowReportContext
{
    /**
     * @param  array<string, string|null>  $filters
     * @param  array<string, class-string<Model>>  $models
     */
    public function __construct(
        protected array $filters,
        protected array $models,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<int, string>
     */
    public function templateIds(): array
    {
        $flowSlug = $this->filters['flow_slug'] ?? null;

        /** @var class-string<FlowStepTemplate> $stepTemplateModel */
        $stepTemplateModel = $this->models['step_template'];

        if (! $flowSlug) {
            return $stepTemplateModel::query()->pluck('id')->all();
        }

        /** @var class-string<Flow> $flowModel */
        $flowModel = $this->models['flow'];

        $flow = $flowModel::query()->where('slug', $flowSlug)->first();

        if (! $flow) {
            return [];
        }

        return $stepTemplateModel::query()
            ->where('flow_id', $flow->id)
            ->pluck('id')
            ->all();
    }

    public function executionQuery(): Builder
    {
        /** @var class-string<FlowExecution> $executionModel */
        $executionModel = $this->models['execution'];

        $query = $executionModel::query()
            ->whereIn('flow_step_template_id', $this->templateIds());

        $responsibleId = $this->filters['responsible_id'] ?? null;

        if ($responsibleId) {
            $query->where('current_responsible_id', $responsibleId);
        }

        $this->applyDateRange($query, 'created_at');

        return $query;
    }

    public function metricQuery(): Builder
    {
        /** @var class-string<FlowMetric> $metricModel */
        $metricModel = $this->models['metric'];

        $query = $metricModel::query()
            ->whereIn('flow_step_template_id', $this->templateIds());

        $this->applyDateRange($query, 'calculated_at');

        return $query;
    }

    public function historyQuery(): Builder
    {
        /** @var class-string<FlowHistory> $historyModel */
        $historyModel = $this->models['history'];
        /** @var class-string<FlowConfigStep> $configStepModel */
        $configStepModel = $this->models['config_step'];

        $query = $historyModel::query()
            ->whereIn('flow_config_step_id', function ($subQuery) use ($configStepModel): void {
                $subQuery->from((new $configStepModel)->getTable())
                    ->select('id')
                    ->whereIn('flow_step_template_id', $this->templateIds());
            });

        $responsibleId = $this->filters['responsible_id'] ?? null;

        if ($responsibleId) {
            $query->where('user_id', $responsibleId);
        }

        $this->applyDateRange($query, 'performed_at');

        return $query;
    }

    /**
     * @param  array<int, string>  $userIds
     * @return array<string, string>
     */
    public function userNamesById(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $userModel = $this->models['user'] ?? null;

        if (! is_string($userModel) || ! class_exists($userModel)) {
            return [];
        }

        return $userModel::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id')
            ->map(fn (mixed $name): string => (string) $name)
            ->toArray();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function flowOptions(): array
    {
        /** @var class-string<Flow> $flowModel */
        $flowModel = $this->models['flow'];

        return $flowModel::query()
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(fn (Flow $flow): array => [
                'value' => (string) $flow->slug,
                'label' => (string) $flow->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function responsibleOptions(): array
    {
        $ids = $this->executionQuery()
            ->whereNotNull('current_responsible_id')
            ->distinct()
            ->pluck('current_responsible_id')
            ->filter()
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        $names = $this->userNamesById($ids);

        return collect($ids)
            ->map(function (string $id) use ($names): ?array {
                if (! isset($names[$id])) {
                    return null;
                }

                return [
                    'value' => $id,
                    'label' => $names[$id],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function applyDateRange(Builder $query, string $column): void
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        if ($dateFrom) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }
}
