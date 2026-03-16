<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

interface FlowReportPresetWithTables extends FlowReportPreset
{
    /**
     * @return array<int, string|array{table: class-string<FlowReportTable>, label?: string, options?: array<string, mixed>}>
     */
    public function tables(): array;
}
