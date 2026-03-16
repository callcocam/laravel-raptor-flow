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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Etapa de workflow associada a um configurável (ex.: Planograma).
 * Relaciona FlowStepTemplate ao configurável; FlowExecution aponta para FlowConfigStep.
 * Estrutura: Planograma → FlowConfigSteps → FlowStepTemplate; FlowExecution → FlowConfigStep.
 */
class FlowConfigStep extends Model
{
    use HasUlids;
    use UsesFlowConnection;

    protected $flowTableBaseName = 'config_steps';

    protected $fillable = [
        'configurable_type',
        'configurable_id',
        'flow_step_template_id',
        'name',
        'description',
        'order',
        'default_role_id',
        'suggested_responsible_id',
        'estimated_duration_days',
        'expected_date',
        'completed_date',
        'is_required',
        'is_active',
        'allow_skip',
        'auto_assign_role',
        'auto_assign_user',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'order' => 'integer',
            'estimated_duration_days' => 'integer',
            'expected_date' => 'date',
            'completed_date' => 'date',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'allow_skip' => 'boolean',
            'auto_assign_role' => 'boolean',
            'auto_assign_user' => 'boolean',
        ];
    }

    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    public function stepTemplate(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'flow_step_template_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(FlowExecution::class, 'flow_config_step_id');
    }

    /**
     * Participantes (possíveis responsáveis) desta etapa.
     * Permite vários usuários por etapa além de suggested_responsible_id.
     */
    public function participants(): MorphMany
    {
        return $this->morphMany(FlowParticipant::class, 'participable');
    }
}
