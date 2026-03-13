<?php

namespace Callcocam\LaravelRaptorFlow\Traits;

use Illuminate\Support\Str;

trait UsesFlowConnection
{
    /**
     * Conexão das tabelas flow_*. Use config('flow.connection') para banco principal (landlord);
     * quando null, usa a conexão default do request.
     */
    public function getConnectionName(): ?string
    {
        return config('flow.connection') ?? parent::getConnectionName();
    }

    public function getTable(): string
    {
        $prefix = config('flow.table_prefix', 'flow_');
        $base = property_exists($this, 'flowTableBaseName') ? $this->flowTableBaseName : $this->guessFlowTableName();

        return $prefix.$base;
    }

    protected function guessFlowTableName(): string
    {
        $name = class_basename($this);
        $name = preg_replace('/^Flow/', '', $name);

        return Str::snake(Str::plural($name));
    }
}
