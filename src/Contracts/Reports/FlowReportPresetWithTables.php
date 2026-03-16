<?php

namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

interface FlowReportPresetWithTables extends FlowReportPreset
{
    /**
     * @return array<int, string|array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}>
     */
    public function tables(): array;
}
