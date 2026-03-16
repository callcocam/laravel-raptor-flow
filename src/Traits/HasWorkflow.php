<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Traits;

use Callcocam\LaravelRaptorFlow\Contracts\Workable;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
    public function workflowExecutions(): MorphMany
    {
        return $this->morphMany(FlowExecution::class, 'workable');
    }
}
