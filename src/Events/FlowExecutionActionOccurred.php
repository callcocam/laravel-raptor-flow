<?php

namespace Callcocam\LaravelRaptorFlow\Events;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlowExecutionActionOccurred
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public FlowExecution $execution,
        public string $action,
        public ?string $actorId = null,
        public ?string $fromStepId = null,
        public ?string $toStepId = null,
        public array $metadata = [],
    ) {}
}
