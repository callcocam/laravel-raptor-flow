<?php

namespace Callcocam\LaravelRaptorFlow\Services\Reports\Tables;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportTable;
use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

class ResponsibleActivityTable implements FlowReportTable
{
    public static function key(): string
    {
        return 'responsible_activity';
    }

    public static function label(): string
    {
        return 'Detalhamento por responsável';
    }

    public function build(FlowReportContext $context, array $options = []): array
    {
        $rows = $context->historyQuery()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as actions_count, avg(duration_in_step_minutes) as avg_duration')
            ->groupBy('user_id')
            ->orderByDesc('actions_count')
            ->limit((int) ($options['limit'] ?? 10))
            ->get();

        $ids = $rows->pluck('user_id')->map(fn (mixed $id): string => (string) $id)->all();
        $names = $context->userNamesById($ids);

        return $rows->map(function ($item) use ($names): array {
            $id = (string) $item->user_id;

            return [
                'responsible_id' => $id,
                'name' => (string) ($names[$id] ?? 'Usuario'),
                'actions_count' => (int) $item->actions_count,
                'avg_duration_minutes' => $item->avg_duration !== null ? (int) round((float) $item->avg_duration) : null,
            ];
        })->values()->all();
    }
}
