<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Enums\FlowAction;
use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FlowHistory extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'histories';

    protected $fillable = [
        'workable_type',
        'workable_id',
        'flow_config_step_id',
        'action',
        'from_step_id',
        'to_step_id',
        'user_id',
        'previous_responsible_id',
        'new_responsible_id',
        'performed_at',
        'duration_in_step_minutes',
        'sla_at_transition',
        'was_overdue',
        'notes',
        'snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'action' => FlowAction::class,
            'performed_at' => 'datetime',
            'sla_at_transition' => 'datetime',
            'was_overdue' => 'boolean',
            'snapshot' => 'array',
            'metadata' => 'array',
            'duration_in_step_minutes' => 'integer',
        ];
    }

    public function workable(): MorphTo
    {
        return $this->morphTo();
    }

    public function configStep(): BelongsTo
    {
        return $this->belongsTo(FlowConfigStep::class, 'flow_config_step_id');
    }

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(FlowConfigStep::class, 'from_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(FlowConfigStep::class, 'to_step_id');
    }
}
