<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Support\Actions\FlowAction;
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

    /** @var array<array{id: string, label: string, url: string, placeholder?: string}> */
    protected array $notes = [];

    /** @var array<array{key: string, label: string, url: string, external?: bool}> */
    protected array $modalLinks = [];

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
        return [
            'sections' => $this->modalSections,
            'actions'  => array_map(fn(FlowAction $a) => $a->toArray(), $this->actions),
            'links'    => $this->modalLinks,
            'notes'    => $this->notes,
        ];
    }

    /**
     * @return array{columns: array<mixed>}
     */
    public function getCardConfig(): array
    {
        return [
            'columns' => $this->cardColumns,
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
