<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowConfig extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'configs';

    protected $fillable = [
        'name',
        'description',
        'configurable_type',
        'configurable_id',
        'workflow_preset_id',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'status' => FlowStatus::class,
        ];
    }

    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FlowConfigStep::class, 'flow_config_id');
    }
}
