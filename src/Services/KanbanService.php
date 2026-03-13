<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Support\Actions\FlowAction;
use Callcocam\LaravelRaptorFlow\Support\Kanban\KanbanBoard;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\ExecutionColumn;
use Closure;

/**
 * Thin wrapper fluente sobre KanbanBoard.
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

    /** @var array<array{key: string, label: string, url: string, external?: bool}> */
    protected array $modalLinks = [];

    // ── Fluent setters ────────────────────────────────────────────────────────

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
     * A URL da action deve ser uma string estática ou conter placeholders {param}
     * que o frontend resolve via resolveActionUrl(). Closures de URL não são
     * suportados neste contexto (getDetailModalConfig é chamado sem execução).
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

    /**
     * @param array{key: string, label: string, url: string, external?: bool} $link
     */
    public function addModalLink(array $link): static
    {
        $this->modalLinks[] = $link;

        return $this;
    }

    // ── Output ────────────────────────────────────────────────────────────────

    /**
     * Constrói e retorna os dados do board delegando ao KanbanBoard.
     */
    public function getBoardData(): array
    {
        return $this->buildBoard()->getBoardData();
    }

    /**
     * Monta o DetailModalConfig para o frontend.
     *
     * As actions são serializadas sem contexto de execução — use placeholders
     * {param} nas URLs em vez de Closures.
     *
     * @return array{sections: array<mixed>, actions: array<mixed>, links: array<mixed>}
     */
    public function getDetailModalConfig(): array
    {
        return [
            'sections' => $this->modalSections,
            'actions'  => array_map(fn(FlowAction $a) => $a->toArray(), $this->actions),
            'links'    => $this->modalLinks,
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
     * Retorna o valor de um filtro ou o default.
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
