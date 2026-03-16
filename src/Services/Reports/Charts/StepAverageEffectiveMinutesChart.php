<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services\Reports\Charts;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportChart;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

class StepAverageEffectiveMinutesChart implements FlowReportChart
{
    public static function key(): string
    {
        return 'step_avg_effective_minutes';
    }

    public static function label(): string
    {
        return 'Tempo medio por etapa';
    }

    public static function defaultType(): string
    {
        return 'bar';
    }

    public function build(FlowReportContext $context, array $options = []): array
    {
        $rows = $context->metricQuery()
            ->selectRaw('flow_step_template_id, avg(effective_work_minutes) as avg_effective, count(*) as total')
            ->groupBy('flow_step_template_id')
            ->orderByDesc('total')
            ->limit((int) ($options['limit'] ?? 10))
            ->get();

        $templateIds = $rows->pluck('flow_step_template_id')->filter()->values()->all();

        $names = empty($templateIds)
            ? collect()
            : FlowStepTemplate::query()->whereIn('id', $templateIds)->pluck('name', 'id');

        return [
            'labels' => $rows->map(function ($item) use ($names): string {
                return (string) ($names[(string) $item->flow_step_template_id] ?? 'Etapa');
            })->all(),
            'datasets' => [
                [
                    'label' => (string) ($options['dataset_label'] ?? 'Tempo medio efetivo (min)'),
                    'data' => $rows->map(fn ($item): int => (int) round((float) $item->avg_effective))->all(),
                ],
            ],
        ];
    }
}
