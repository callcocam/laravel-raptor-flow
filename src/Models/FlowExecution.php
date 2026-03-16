<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowExecution extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $fillable = [
        'workable_type',
        'workable_id',
        'flow_config_step_id',
        'flow_step_template_id',
        'status',
        'current_responsible_id',
        'execution_started_by',
        'started_at',
        'completed_at',
        'sla_date',
        'paused_at',
        'paused_duration_minutes',
        'actual_duration_minutes',
        'estimated_duration_days',
        'notes',
        'context',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'sla_date' => 'datetime',
            'paused_at' => 'datetime',
            'paused_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',
            'estimated_duration_days' => 'integer',
            'status' => FlowStatus::class,
        ];
    }

    public function executionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function configStep(): BelongsTo
    {
        return $this->belongsTo(FlowConfigStep::class, 'flow_config_step_id');
    }

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'flow_step_template_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(FlowMetric::class, 'workable_id', 'workable_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(FlowNotification::class, 'notifiable_id', 'workable_id');
    }
}
