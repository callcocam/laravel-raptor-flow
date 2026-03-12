<?php

namespace Callcocam\LaravelRaptorFlow\Traits;

trait UsesFlowConnection
{
    public function getConnectionName(): ?string
    {
        return config('flow.connection') ?? parent::getConnectionName();
    }

    protected function getTable(): string
    {
        $prefix = config('flow.table_prefix', 'flow_');
        $base = property_exists($this, 'flowTableBaseName') ? $this->flowTableBaseName : $this->guessFlowTableName();

        return $prefix.$base;
    }

    protected function guessFlowTableName(): string
    {
        $name = class_basename($this);
        $name = preg_replace('/^Flow/', '', $name);

        return \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($name));
    }
}
