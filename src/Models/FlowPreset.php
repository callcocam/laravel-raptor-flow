<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowPreset extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'workable_type',
        'is_default',
        'is_active',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FlowPresetStep::class, 'workflow_preset_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultPreset(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeForWorkableType(Builder $query, ?string $workableType): Builder
    {
        if (! $workableType) {
            return $query;
        }

        return $query->where(function (Builder $innerQuery) use ($workableType): void {
            $innerQuery
                ->where('workable_type', $workableType)
                ->orWhereNull('workable_type');
        });
    }

    public static function resolveDefaultFor(?string $workableType = null): ?self
    {
        return static::query()
            ->active()
            ->defaultPreset()
            ->forWorkableType($workableType)
            ->orderByRaw('case when workable_type is null then 1 else 0 end')
            ->first();
    }
}
