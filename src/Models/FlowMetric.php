<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FlowMetric extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'metrics';

    protected $fillable = [
        'workable_type',
        'workable_id',
        'flow_config_step_id',
        'flow_step_template_id',
        'total_duration_minutes',
        'effective_work_minutes',
        'estimated_duration_minutes',
        'deviation_minutes',
        'is_on_time',
        'is_rework',
        'rework_count',
        'started_at',
        'completed_at',
        'calculated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'total_duration_minutes' => 'integer',
            'effective_work_minutes' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'deviation_minutes' => 'integer',
            'is_on_time' => 'boolean',
            'is_rework' => 'boolean',
            'rework_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'calculated_at' => 'datetime',
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

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'flow_step_template_id');
    }
}
