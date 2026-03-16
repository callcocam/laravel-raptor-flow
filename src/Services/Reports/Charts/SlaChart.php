<?php

namespace Callcocam\LaravelRaptorFlow\Services\Reports\Charts;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportChart;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

class SlaChart implements FlowReportChart
{
    public static function key(): string
    {
        return 'sla';
    }

    public static function label(): string
    {
        return 'SLA on-time vs overdue';
    }

    public static function defaultType(): string
    {
        return 'doughnut';
    }

    public function build(FlowReportContext $context, array $options = []): array
    {
        $slaRaw = $context->metricQuery()
            ->selectRaw('is_on_time, count(*) as total')
            ->groupBy('is_on_time')
            ->pluck('total', 'is_on_time')
            ->toArray();

        return [
            'labels' => ['No prazo', 'Fora do prazo'],
            'datasets' => [
                [
                    'label' => (string) ($options['dataset_label'] ?? 'Quantidade'),
                    'data' => [
                        (int) ($slaRaw[1] ?? 0),
                        (int) ($slaRaw[0] ?? 0),
                    ],
                ],
            ],
        ];
    }
}
