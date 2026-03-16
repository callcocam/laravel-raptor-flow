<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services\Reports\Presets;

use Callcocam\LaravelRaptorFlow\Contracts\Reports\FlowReportPresetWithTables;
use Callcocam\LaravelRaptorFlow\Services\Reports\Charts\ResponsibleChart;
use Callcocam\LaravelRaptorFlow\Services\Reports\Charts\SlaChart;
use Callcocam\LaravelRaptorFlow\Services\Reports\Charts\StatusChart;
use Callcocam\LaravelRaptorFlow\Services\Reports\Charts\StepAverageEffectiveMinutesChart;
use Callcocam\LaravelRaptorFlow\Services\Reports\Tables\ResponsibleActivityTable;

class OverviewFlowReportPreset implements FlowReportPresetWithTables
{
    public function charts(): array
    {
        return [
            ['chart' => StatusChart::class, 'type' => 'doughnut'],
            ['chart' => ResponsibleChart::class, 'type' => 'horizontal-bar'],
            ['chart' => SlaChart::class, 'type' => 'doughnut'],
            ['chart' => StepAverageEffectiveMinutesChart::class, 'type' => 'bar'],
        ];
    }

    public function tables(): array
    {
        return [
            ResponsibleActivityTable::class,
        ];
    }
}
