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
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FlowPresetStep extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'preset_steps';

    protected $appends = ['users'];

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

    public function participants(): MorphMany
    {
        return $this->morphMany(FlowParticipant::class, 'participable');
    }

    /**
     * @return array<int, string>
     */
    public function getUsersAttribute(): array
    {
        if ($this->relationLoaded('participants')) {
            $participantUsers = $this->participants
                ->pluck('user_id')
                ->map(fn ($value) => (string) $value)
                ->filter(fn (string $value) => $value !== '')
                ->unique()
                ->values()
                ->all();

            if ($participantUsers !== []) {
                return $participantUsers;
            }
        }

        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return $this->normalizeUsers($metadata['users'] ?? $metadata['default_users'] ?? []);
    }

    /**
     * @param  mixed  $usersPayload
     * @return array<int, string>
     */
    protected function normalizeUsers(mixed $usersPayload): array
    {
        if (is_null($usersPayload) || $usersPayload === '') {
            return [];
        }

        if (is_string($usersPayload)) {
            $usersPayload = str_contains($usersPayload, ',')
                ? explode(',', $usersPayload)
                : [$usersPayload];
        }

        if (! is_array($usersPayload)) {
            $usersPayload = [$usersPayload];
        }

        return collect($usersPayload)
            ->map(function ($value) {
                if (is_array($value)) {
                    return $value['id'] ?? $value['value'] ?? null;
                }

                return $value;
            })
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    }
}
