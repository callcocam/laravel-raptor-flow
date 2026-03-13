<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\ExecutionColumn;
use Callcocam\LaravelRaptorFlow\Support\Kanban\KanbanBoard;
use Closure;
use Illuminate\Support\Collection;

/**
 * Serviço Kanban do pacote: parte do Flow e delega ao KanbanBoard.
 *
 * O app configura workableType, workableIds, filters, columns, etc. e chama getBoardData().
 * Os steps vêm do flow; groupConfigs são calculados a partir dos FlowConfigSteps do fluxo
 * (agrupados por configurável: id, name, stepIds).
 *
 * Uso no app (ex. Plannerate):
 *   $this->kanbanService
 *       ->setFlow($flow)
 *       ->setWorkableType(GondolaWorkflow::class)
 *       ->setWorkableIds(fn () => $gondolasCache->keys()->toArray())
 *       ->setFilters($filters)
 *       ->setColumns([...])
 *       ->getBoardData();
 */
class KanbanService
{
    protected ?Flow $flow = null;

    protected string $workableType = '';

    /** @var Closure|array|null */
    protected $workableIdsResolver = null;

    protected array $filters = [];

    /** @var ExecutionColumn[] */
    protected array $columns = [];

    protected bool $withDetailModal = false;

    protected ?Closure $additionalQueryCallback = null;

    protected ?Closure $userRolesResolver = null;

    /** @var Closure|array|null groupConfigs customizados; se null, são calculados a partir do flow */
    protected $groupConfigsResolver = null;

    public function setFlow(Flow $flow): self
    {
        $this->flow = $flow;

        return $this;
    }

    public function setWorkableType(string $class): self
    {
        $this->workableType = $class;

        return $this;
    }

    public function setWorkableIds(Closure|array $ids): self
    {
        $this->workableIdsResolver = $ids;

        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param  ExecutionColumn[]  $columns
     */
    public function setColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function setWithDetailModal(bool $enable = true): self
    {
        $this->withDetailModal = $enable;

        return $this;
    }

    public function setAdditionalQuery(?Closure $callback): self
    {
        $this->additionalQueryCallback = $callback;

        return $this;
    }

    public function setUserRoles(?Closure $resolver): self
    {
        $this->userRolesResolver = $resolver;

        return $this;
    }

    /**
     * groupConfigs customizados. Se null, getBoardData() calcula a partir do flow
     * (FlowConfigStep do fluxo agrupados por configurable_id).
     */
    public function setGroupConfigs(Closure|array|null $resolver): self
    {
        $this->groupConfigsResolver = $resolver;

        return $this;
    }

    /**
     * Retorna o mesmo formato do KanbanBoard: board (steps + executions), groupConfigs, userRoles, filters.
     */
    public function getBoardData(): array
    {
        $board = KanbanBoard::make()
            ->flow($this->flow)
            ->workableType($this->workableType)
            ->workableIds($this->workableIdsResolver ?? [])
            ->filters($this->filters)
            ->withDetailModal($this->withDetailModal)
            ->groupConfigs($this->groupConfigsResolver ?? fn () => $this->getGroupConfigsFromFlow())
            ->columns($this->columns);

        if ($this->additionalQueryCallback !== null) {
            $board->additionalQuery($this->additionalQueryCallback);
        }

        if ($this->userRolesResolver !== null) {
            $board->userRoles($this->userRolesResolver);
        }

        return $board->getBoardData();
    }

    /**
     * Opções para os filtros do painel (planogramas, lojas, usuários, etc.).
     * O app pode sobrescrever para retornar dados de domínio.
     *
     * @return array<int, mixed>
     */
    public function getFilterOptionsData(): array
    {
        return [];
    }

    /**
     * groupConfigs a partir do flow: FlowConfigSteps dos templates do fluxo,
     * agrupados por configurable_id; cada grupo tem id, name (label do configurável), stepIds.
     *
     * @return array<int, array{id: string, name: string, stepIds: array<string>}>
     */
    protected function getGroupConfigsFromFlow(): array
    {
        if ($this->flow === null) {
            return [];
        }

        $templateIds = $this->flow->stepTemplates()
            ->where('is_active', true)
            ->orderBy('suggested_order')
            ->pluck('id')
            ->toArray();

        if (empty($templateIds)) {
            return [];
        }

        $stepsByConfigurable = FlowConfigStep::query()
            ->whereIn('flow_step_template_id', $templateIds)
            ->with('configurable')
            ->orderBy('order')
            ->get()
            ->groupBy('configurable_id');

        return $stepsByConfigurable->map(function (Collection $steps, string $configurableId) {
            $configurable = $steps->first()?->configurable;

            return [
                'id' => $configurableId,
                'name' => $configurable?->getWorkflowLabel() ?? $configurableId,
                'stepIds' => $steps->sortBy('order')->pluck('flow_step_template_id')->values()->toArray(),
            ];
        })->values()->toArray();
    }
}
