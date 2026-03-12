<?php

namespace Callcocam\LaravelRaptorFlow\Traits;

use Callcocam\LaravelRaptorFlow\Contracts\Workable;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;

trait HasWorkflow
{
    public function getWorkflowKey(): string
    {
        return (string) $this->getKey();
    }

    public function getWorkflowLabel(): string
    {
        return $this->name ?? $this->title ?? (string) $this->getKey();
    }

    /**
     * Execuções de workflow em que este model é o workable.
     */
    public function workflowExecutions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(FlowExecution::class, 'workable');
    }
}
