<?php

namespace Callcocam\LaravelRaptorFlow\Services\Reports\Charts;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportChart;
use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

class StatusChart implements FlowReportChart
{
    public static function key(): string
    {
        return 'status';
    }

    public static function label(): string
    {
        return 'Andamento por status';
    }

    public static function defaultType(): string
    {
        return 'doughnut';
    }

    public function build(FlowReportContext $context, array $options = []): array
    {
        $statusRaw = $context->executionQuery()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusLabels = [
            FlowStatus::Pending->value => 'Pendente',
            FlowStatus::InProgress->value => 'Em andamento',
            FlowStatus::Paused->value => 'Pausado',
            FlowStatus::Completed->value => 'Concluido',
            FlowStatus::Blocked->value => 'Bloqueado',
        ];

        $labels = [];
        $data = [];

        foreach ($statusLabels as $status => $label) {
            $labels[] = $label;
            $data[] = (int) ($statusRaw[$status] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => (string) ($options['dataset_label'] ?? 'Quantidade'),
                    'data' => $data,
                ],
            ],
        ];
    }
}
