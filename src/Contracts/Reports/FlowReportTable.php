<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts\Reports;

use Callcocam\LaravelRaptorFlow\Support\Reports\FlowReportContext;

interface FlowReportTable
{
    public static function key(): string;

    public static function label(): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function build(FlowReportContext $context, array $options = []): array;
}
