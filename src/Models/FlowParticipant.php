<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Enums\FlowParticipantRole;
use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FlowParticipant extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $fillable = [
        'user_id',
        'participable_type',
        'participable_id',
        'role_in_step',
        'is_pre_assigned',
        'assigned_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role_in_step' => FlowParticipantRole::class,
            'is_pre_assigned' => 'boolean',
            'assigned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function participable(): MorphTo
    {
        return $this->morphTo();
    }
}
