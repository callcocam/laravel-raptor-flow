<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

interface FlowReportPreset
{
    /**
     * @return array<int, string|array{chart: class-string<FlowReportChart>, type?: string, label?: string, options?: array<string, mixed>}>
     */
    public function charts(): array;
}
