<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

interface FlowReportChart
{
    public static function key(): string;

    public static function label(): string;

    public static function defaultType(): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, int>}>}
     */
    public function build(FlowReportContext $context, array $options = []): array;
}
