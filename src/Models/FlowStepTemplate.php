<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowStepTemplate extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'instructions',
        'category',
        'tags',
        'suggested_order',
        'estimated_duration_days',
        'default_role_id',
        'color',
        'icon',
        'is_required_by_default',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'array',
            'suggested_order' => 'integer',
            'estimated_duration_days' => 'integer',
            'is_required_by_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function presetSteps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FlowPresetStep::class, 'workflow_step_template_id');
    }

    public function configSteps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FlowConfigStep::class, 'flow_step_template_id');
    }
}
