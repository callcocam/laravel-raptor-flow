<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Enums\FlowNotificationPriority;
use Callcocam\LaravelRaptorFlow\Enums\FlowNotificationType;
use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowNotification extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $fillable = [
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'flow_config_step_id',
        'type',
        'priority',
        'title',
        'message',
        'link',
        'is_read',
        'read_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => FlowNotificationType::class,
            'priority' => FlowNotificationPriority::class,
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function configStep(): BelongsTo
    {
        return $this->belongsTo(FlowConfigStep::class, 'flow_config_step_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
