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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FlowStepTemplate extends Model
{
    use HasUlids;
    use SoftDeletes;
    use UsesFlowConnection;

    protected $appends = ['users'];

    protected $fillable = [
        'user_id',
        'tenant_id',
        'flow_id',
        'template_next_step_id',
        'template_previous_step_id',
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

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class, 'flow_id');
    }

    public function presetSteps(): HasMany
    {
        return $this->hasMany(FlowPresetStep::class, 'workflow_step_template_id');
    }

    public function configSteps(): HasMany
    {
        return $this->hasMany(FlowConfigStep::class, 'flow_step_template_id');
    }

    /**
     * Próximo template na sequência.
     */
    public function templateNextStep(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'template_next_step_id');
    }

    /**
     * Template anterior na sequência.
     */
    public function templatePreviousStep(): BelongsTo
    {
        return $this->belongsTo(FlowStepTemplate::class, 'template_previous_step_id');
    }

    /**
     * @return array<int, string>
     */
    public function getUsersAttribute(): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return collect($metadata['suggested_users'] ?? [])
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Retorna os templates padrão (ex.: planograma).
     * Use flow:seed-templates para criar no banco.
     *
     * @return array<int, array{name: string, slug: string, description: string, instructions: string, category: string, suggested_order: int, estimated_duration_days: int, is_required_by_default: bool, color: string, icon: string, tags: array}>
     */
    public static function getDefaultTemplates(): array
    {
        $defaults = [
            [
                'name' => 'Criação do planograma',
                'description' => 'Criação inicial do planograma com definição de produtos e layout',
                'instructions' => 'Definir produtos, posicionamento e layout inicial do planograma',
                'category' => 'criacao',
                'suggested_order' => 1,
                'estimated_duration_days' => 2,
                'is_required_by_default' => true,
                'color' => 'blue',
                'icon' => 'layout-grid',
                'tags' => ['inicial', 'obrigatoria'],
            ],
            [
                'name' => 'Avaliação do Comercial',
                'description' => 'Análise comercial do planograma proposto',
                'instructions' => 'Revisar aspectos comerciais, margem e estratégia de vendas',
                'category' => 'analise',
                'suggested_order' => 2,
                'estimated_duration_days' => 3,
                'is_required_by_default' => true,
                'color' => 'yellow',
                'icon' => 'trending-up',
                'tags' => ['comercial', 'analise'],
            ],
            [
                'name' => 'Aprovação da área de GC',
                'description' => 'Aprovação pela área de Gerenciamento de Categoria',
                'instructions' => 'Validar alinhamento com estratégia de categoria e políticas',
                'category' => 'aprovacao',
                'suggested_order' => 3,
                'estimated_duration_days' => 2,
                'is_required_by_default' => true,
                'color' => 'purple',
                'icon' => 'check-circle',
                'tags' => ['aprovacao', 'gc'],
            ],
            [
                'name' => 'Revisão final',
                'description' => 'Revisão final antes da implementação',
                'instructions' => 'Verificar todos os aspectos antes da implementação final',
                'category' => 'revisao',
                'suggested_order' => 4,
                'estimated_duration_days' => 1,
                'is_required_by_default' => true,
                'color' => 'indigo',
                'icon' => 'eye',
                'tags' => ['revisao', 'final'],
            ],
            [
                'name' => 'Logística e abastecimento',
                'description' => 'Preparação logística e garantia de abastecimento',
                'instructions' => 'Coordenar logística e garantir disponibilidade de produtos',
                'category' => 'logistica',
                'suggested_order' => 5,
                'estimated_duration_days' => 3,
                'is_required_by_default' => true,
                'color' => 'green',
                'icon' => 'truck',
                'tags' => ['logistica', 'abastecimento'],
            ],
            [
                'name' => 'Execução Loja',
                'description' => 'Implementação do planograma na loja',
                'instructions' => 'Implementar fisicamente o planograma na loja',
                'category' => 'execucao',
                'suggested_order' => 6,
                'estimated_duration_days' => 1,
                'is_required_by_default' => true,
                'color' => 'red',
                'icon' => 'store',
                'tags' => ['execucao', 'loja'],
            ],
        ];

        foreach ($defaults as $i => $row) {
            $defaults[$i]['slug'] = Str::slug($row['name']);
        }

        return $defaults;
    }

    
    /**
     * Retorna as categorias disponíveis
     */
    public static function getCategories(): array
    {
        return [
            'criacao' => 'Criação',
            'analise' => 'Análise',
            'aprovacao' => 'Aprovação',
            'revisao' => 'Revisão',
            'logistica' => 'Logística',
            'execucao' => 'Execução',
            'validacao' => 'Validação',
            'finalizacao' => 'Finalização',
        ];
    }

    /**
     * Retorna as cores disponíveis para templates
     */
    public static function getColors(): array
    {
        return [
            'blue' => 'Azul',
            'green' => 'Verde',
            'yellow' => 'Amarelo',
            'red' => 'Vermelho',
            'purple' => 'Roxo',
            'pink' => 'Rosa',
            'indigo' => 'Índigo',
            'gray' => 'Cinza',
        ];
    }

}
