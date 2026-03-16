<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Enums\FlowAction;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowHistory;

class RecordFlowHistoryService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(FlowExecution $execution, FlowAction $action, array $attributes = []): FlowHistory
    {
        return FlowHistory::create(array_merge([
            'workable_type' => $execution->workable_type,
            'workable_id' => $execution->workable_id,
            'flow_config_step_id' => $execution->flow_config_step_id,
            'action' => $action,
            'performed_at' => now(),
        ], $attributes));
    }
}
