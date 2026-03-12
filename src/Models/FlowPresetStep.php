<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowPresetStep extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'preset_steps';

    protected $fillable = [
        'workflow_preset_id',
        'workflow_step_template_id',
        'order',
        'name',
        'default_role_id',
        'suggested_responsible_id',
        'estimated_duration_days',
        'is_required',
        'auto_assign_role',
        'auto_assign_user',
        'allow_skip',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'order' => 'integer',
            'estimated_duration_days' => 'integer',
            'is_required' => 'boolean',
            'auto_assign_role' => 'boolean',
            'auto_assign_user' => 'boolean',
            'allow_skip' => 'boolean',
        ];
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(FlowPreset::class, 'workflow_preset_id');
    }

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'workflow_step_template_id');
    }
}
