<?php

namespace Callcocam\LaravelRaptorFlow\Models;

use Callcocam\LaravelRaptorFlow\Traits\UsesFlowConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Fluxo (flow): agrupa step templates, configs e execuções.
 * Ex.: "Gerenciamento de planogramas", "Aprovação de pedidos".
 */
class Flow extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'flows';

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function stepTemplates(): HasMany
    {
        return $this->hasMany(FlowStepTemplate::class, 'flow_id');
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }

    public static function createWithSlug(array $attributes): self
    {
        if (empty($attributes['slug']) && ! empty($attributes['name'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        return static::create($attributes);
    }
}
