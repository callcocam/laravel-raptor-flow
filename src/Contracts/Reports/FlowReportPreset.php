<?php

namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

interface FlowReportPreset
{
    /**
     * @return array<int, string|array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}>
     */
    public function charts(): array;
}
