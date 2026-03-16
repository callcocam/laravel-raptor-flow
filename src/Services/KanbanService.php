<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Support\Actions\FlowAction;
use Callcocam\LaravelRaptorFlow\Support\Builders\ConfigureKanbanCard;
use Callcocam\LaravelRaptorFlow\Support\Builders\ConfigureKanbanModal;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplayColumn;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplaySection;
use Callcocam\LaravelRaptorFlow\Support\Display\NotesBlock;
use Callcocam\LaravelRaptorFlow\Support\Kanban\KanbanBoard;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\ExecutionColumn;
use Closure;

/**
 * Camada fluente sobre o KanbanBoard.
 *
 * Responsabilidades:
 *   - Acumular configuração via setters fluentes
 *   - Delegar a construção do board ao KanbanBoard
 *   - Serializar actions para getDetailModalConfig()
 *
 * Para extensão de domínio, sobrescreva getBoardData() na subclasse:
 *   1. Carregue dados de domínio
 *   2. Configure os setters fluentes (setWorkableType, setWorkableIds, addColumn, etc.)
 *   3. Chame parent::getBoardData()
 */
class KanbanService
{
    protected ?Flow $flow = null;

    protected string $workableType = '';

    protected array $filters = [];

    protected bool $withDetailModal = false;

    protected Closure|array|null $workableIdsResolver = null;

    protected ?Closure $groupConfigsResolver = null;

    protected ?Closure $userRolesResolver = null;

    protected ?Closure $additionalQueryCallback = null;

    /** @var ExecutionColumn[] */
    protected array $columns = [];

    /** @var FlowAction[] */
    protected array $actions = [];

    /** @var array<array{id: string, label?: string, fields: array<array{key: string, type: string, label?: string}>}> */
    protected array $modalSections = [];

    /** @var array<array{id: string, label?: string, style?: string, fields: array<mixed>}> */
    protected array $cardColumns = [];

    /** @var array<array{key: string, label: string, url: string, position?: string, priority?: int, external?: bool}> */
    protected array $cardLinks = [];

    /** @var array<array{id: string, label: string, url: string, placeholder?: string}> */
    protected array $notes = [];

    /** @var array<array{key: string, label: string, url: string, external?: bool}> */
    protected array $modalLinks = [];

    protected ?ConfigureKanbanModal $modalBuilder = null;

    protected ?ConfigureKanbanCard $cardBuilder = null;

    // ── Setters fluentes ──────────────────────────────────────────────────────

    public function setFlow(Flow $flow): static
    {
        $this->flow = $flow;

        return $this;
    }

    public function setFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function setWorkableType(string $class): static
    {
        $this->workableType = $class;

        return $this;
    }

    /**
     * Define os IDs dos workables a serem exibidos no board.
     * Pode ser um array direto ou um Closure que retorna array.
     */
    public function setWorkableIds(Closure|array $ids): static
    {
        $this->workableIdsResolver = $ids;

        return $this;
    }

    public function setGroupConfigs(Closure $resolver): static
    {
        $this->groupConfigsResolver = $resolver;

        return $this;
    }

    public function setUserRoles(Closure $resolver): static
    {
        $this->userRolesResolver = $resolver;

        return $this;
    }

    public function setAdditionalQuery(Closure $callback): static
    {
        $this->additionalQueryCallback = $callback;

        return $this;
    }

    public function withDetailModal(bool $enable = true): static
    {
        $this->withDetailModal = $enable;

        return $this;
    }

    public function addColumn(ExecutionColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Registra uma action para o modal de detalhes.
     *
     * A URL da action pode ser uma string estática, conter placeholders {param}
     * ou ser definida por callback sem dependências.
     */
    public function addAction(FlowAction $action): static
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * @param array{id: string, label?: string, fields: array<array{key: string, type: string, label?: string, placeholder?: string, readOnly?: bool}>} $section
     */
    public function addModalSection(array $section): static
    {
        $this->modalSections[] = $section;

        return $this;
    }

    public function addSection(DisplaySection $section): static
    {
        $this->modalSections[] = $section->toArray();

        return $this;
    }

    public function addCardColumn(DisplayColumn $column): static
    {
        $this->cardColumns[] = $column->toArray();

        return $this;
    }

    /**
     * @param  array{key: string, label: string, url: string, position?: 'primary'|'secondary', priority?: int, external?: bool}  $link
     */
    public function addCardLink(array $link): static
    {
        $this->cardLinks[] = [
            'key' => $link['key'],
            'label' => $link['label'],
            'url' => $link['url'],
            'position' => in_array($link['position'] ?? 'secondary', ['primary', 'secondary'], true)
                ? ($link['position'] ?? 'secondary')
                : 'secondary',
            'priority' => (int) ($link['priority'] ?? 0),
            'external' => (bool) ($link['external'] ?? false),
        ];

        return $this;
    }

    public function addNote(NotesBlock $block): static
    {
        $this->notes[] = $block->toArray();

        return $this;
    }

    /**
     * @param array{key: string, label: string, url: string, external?: bool} $link
     */
    public function addModalLink(array $link): static
    {
        $this->modalLinks[] = $link;

        return $this;
    }

    public function modal(ConfigureKanbanModal $modal): static
    {
        $this->modalBuilder = $modal;

        return $this;
    }

    public function card(ConfigureKanbanCard $card): static
    {
        $this->cardBuilder = $card;

        return $this;
    }

    // ── Saida ─────────────────────────────────────────────────────────────────

    /**
        * Constrói e retorna os dados do board no formato canônico do KanbanBoard.
     *
     * @return array{board: array{steps: array<mixed>, executions: array<string, array<mixed>>}, groupConfigs: array<mixed>, userRoles: array<mixed>, filters: array<mixed>, cardConfig: array{columns: array<mixed>}}
     */
    public function getBoardData(): array
    {
        $raw = $this->buildBoard()->getBoardData();

        return [
            'board' => $raw['board'] ?? ['steps' => [], 'executions' => []],
            'groupConfigs' => $raw['groupConfigs'] ?? [],
            'userRoles' => $raw['userRoles'] ?? [],
            'filters' => $raw['filters'] ?? [],
            'cardConfig' => $this->getCardConfig(),
        ];
    }

    /**
     * Monta o DetailModalConfig para o frontend.
     *
     * Acoes e notes sao serializados no backend; o frontend apenas renderiza
     * e despacha as requisicoes.
     *
     * @return array{sections: array<mixed>, actions: array<mixed>, links: array<mixed>, notes: array<mixed>}
     */
    public function getDetailModalConfig(): array
    {
        $builderConfig = $this->modalBuilder?->toArray() ?? [
            'sections' => [],
            'actions' => [],
            'links' => [],
            'notes' => [],
        ];

        return [
            'sections' => array_merge($builderConfig['sections'], $this->modalSections),
            'actions'  => array_merge(
                $builderConfig['actions'],
                array_map(fn (FlowAction $a) => $a->toArray(), $this->actions)
            ),
            'links'    => array_merge($builderConfig['links'], $this->modalLinks),
            'notes'    => array_merge($builderConfig['notes'], $this->notes),
        ];
    }

    /**
     * @return array{columns: array<mixed>, links: array<mixed>}
     */
    public function getCardConfig(): array
    {
        $builderConfig = $this->cardBuilder?->toArray() ?? ['columns' => [], 'links' => []];

        $legacyLinks = $this->cardLinks;
        usort($legacyLinks, function (array $left, array $right): int {
            $priorityCompare = ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);

            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? ''));
        });

        return [
            'columns' => array_merge($builderConfig['columns'], $this->cardColumns),
            'links' => array_merge($builderConfig['links'], $legacyLinks),
        ];
    }

    /**
     * Stub extensível para opções dos filtros.
     * Subclasses devem sobrescrever para retornar os dados reais.
     */
    public function getFilterOptionsData(): array
    {
        return [];
    }

    // ── Helpers para subclasses ───────────────────────────────────────────────

    /**
     * Verifica se um filtro está definido e não vazio.
     * Mantido para uso em subclasses (ex: App\Services\Workflow\KanbanService).
     */
    protected function hasFilter(string $key): bool
    {
        return isset($this->filters[$key])
            && $this->filters[$key] !== ''
            && $this->filters[$key] !== null;
    }

    /**
    * Retorna o valor de um filtro ou o padrao.
     * Mantido para uso em subclasses (ex: App\Services\Workflow\KanbanService).
     */
    protected function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    // ── Interno ───────────────────────────────────────────────────────────────

    protected function buildBoard(): KanbanBoard
    {
        $board = KanbanBoard::make()
            ->flow($this->flow)
            ->workableType($this->workableType)
            ->filters($this->filters)
            ->withDetailModal($this->withDetailModal)
            ->columns($this->columns);

        $legacyActions = $this->actions;
        $modalBuilder = $this->modalBuilder;
        $cardBuilder = $this->cardBuilder;

        if ($modalBuilder !== null || $legacyActions !== []) {
            $board->modalActions(function ($execution) use ($modalBuilder, $legacyActions) {
                $builderActions = $modalBuilder?->resolveActionsForExecution($execution) ?? [];
                $legacyResolved = array_values(array_map(
                    fn (FlowAction $action) => $action->toArray($execution),
                    array_filter($legacyActions, fn (FlowAction $action) => $action->isVisible($execution)),
                ));

                return array_merge($builderActions, $legacyResolved);
            });
        }

        if ($cardBuilder !== null) {
            $board->cardActions(fn ($execution) => $cardBuilder->resolveActionsForExecution($execution));
            $board->cardLinks(fn ($execution) => $cardBuilder->resolveLinksForExecution($execution));
        } elseif ($this->cardLinks !== []) {
            $legacyLinks = $this->cardLinks;
            $board->cardLinks(function ($execution) use ($legacyLinks) {
                $resolved = array_map(function (array $link) use ($execution) {
                    $url = str_replace('{id}', (string) $execution->id, $link['url']);
                    $url = str_replace('{workable.id}', (string) ($execution->workable_id ?? ''), $url);

                    return [
                        'key' => $link['key'],
                        'label' => $link['label'],
                        'url' => $url,
                        'position' => $link['position'] ?? 'secondary',
                        'priority' => (int) ($link['priority'] ?? 0),
                        'external' => (bool) ($link['external'] ?? false),
                    ];
                }, $legacyLinks);

                usort($resolved, function (array $left, array $right): int {
                    $priorityCompare = ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);

                    if ($priorityCompare !== 0) {
                        return $priorityCompare;
                    }

                    return strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? ''));
                });

                return $resolved;
            });
        }

        if ($this->workableIdsResolver !== null) {
            $board->workableIds($this->workableIdsResolver);
        }

        if ($this->groupConfigsResolver !== null) {
            $board->groupConfigs($this->groupConfigsResolver);
        }

        if ($this->userRolesResolver !== null) {
            $board->userRoles($this->userRolesResolver);
        }

        if ($this->additionalQueryCallback !== null) {
            $board->additionalQuery($this->additionalQueryCallback);
        }

        return $board;
    }
}
