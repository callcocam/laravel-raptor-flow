<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services\Reports\Charts;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportChart;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

class ResponsibleChart implements FlowReportChart
{
    public static function key(): string
    {
        return 'responsible';
    }

    public static function label(): string
    {
        return 'Andamento por responsavel';
    }

    public static function defaultType(): string
    {
        return 'horizontal-bar';
    }

    public function build(FlowReportContext $context, array $options = []): array
    {
        $rows = $context->executionQuery()
            ->whereNotNull('current_responsible_id')
            ->selectRaw('current_responsible_id, count(*) as total')
            ->groupBy('current_responsible_id')
            ->orderByDesc('total')
            ->limit((int) ($options['limit'] ?? 10))
            ->get();

        $ids = $rows->pluck('current_responsible_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $names = $context->userNamesById($ids);

        return [
            'labels' => $rows->map(
                fn ($item): string => (string) ($names[(string) $item->current_responsible_id] ?? 'Nao atribuido')
            )->all(),
            'datasets' => [
                [
                    'label' => (string) ($options['dataset_label'] ?? 'Quantidade'),
                    'data' => $rows->map(fn ($item): int => (int) $item->total)->all(),
                ],
            ],
        ];
    }
}
