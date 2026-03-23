<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Kanban;

use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Models\Flow;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowNotification;
use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;
use Callcocam\LaravelRaptorFlow\Policies\FlowExecutionPolicy;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\ExecutionColumn;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builder fluente para construção dos dados do board Kanban.
 *
 * O pacote cuida da lógica genérica (steps, executions, agrupamento, filtros genéricos).
 * O app consumidor fornece:
 *   - workableIds: quais IDs de workable exibir (após filtros de domínio)
 *   - columns: como enriquecer cada execução com dados de domínio
 *   - groupConfigs: resolver para os dados de grupo (planogramas, projetos, etc.)
 *   - additionalQuery: constraints extras na query de executions
 *
 * Uso típico:
 *   KanbanBoard::make()
 *       ->workableType(GondolaWorkflow::class)
 *       ->workableIds(fn() => $gondolasCache->keys()->toArray())
 *       ->filters($filters)
 *       ->withDetailModal(true)
 *       ->groupConfigs(fn() => [...])
 *       ->columns([
 *           WorkableColumn::make('workable')->resolveUsing(fn($exec, $ctx) => [...]),
 *           PermissionsColumn::make('permissions')->resolveUsing(fn($exec, $ctx) => [...]),
 *       ])
 *       ->getBoardData();
 */
class KanbanBoard
{
    protected ?Flow $flow = null;

    protected string $workableType = '';

    protected array $filters = [];

    protected bool $withDetailModal = false;

    protected Closure|array|null $workableIdsResolver = null;

    protected ?Closure $groupConfigsResolver = null;

    protected ?Closure $userRolesResolver = null;

    protected ?Closure $additionalQueryCallback = null;

    protected ?Closure $modalActionsResolver = null;

    protected ?Closure $cardActionsResolver = null;

    protected ?Closure $cardLinksResolver = null;

    /** @var ExecutionColumn[] */
    protected array $columns = [];

    public static function make(): static
    {
        return new static;
    }

    /**
     * Escopo por fluxo: steps passam a vir do flow (flow_step_templates do fluxo).
     * Se não informado, usa todos os FlowStepTemplate ativos (comportamento legado).
     */
    public function flow(?Flow $flow): static
    {
        $this->flow = $flow;

        return $this;
    }

    public function workableType(string $class): static
    {
        $this->workableType = $class;

        return $this;
    }

    /**
     * Define os IDs dos workables a serem exibidos no board.
     * Pode ser um array ou um Closure que retorna array.
     * Tipicamente o app filtra por domínio (planogram, loja, etc.) antes de chamar isso.
     */
    public function workableIds(Closure|array $ids): static
    {
        $this->workableIdsResolver = $ids;

        return $this;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function withDetailModal(bool $enable = true): static
    {
        $this->withDetailModal = $enable;

        return $this;
    }

    /**
     * Resolver para os groupConfigs (planogramas, projetos, etc.).
     * O Closure retorna array de { id, name, stepIds[] }.
     */
    public function groupConfigs(Closure $resolver): static
    {
        $this->groupConfigsResolver = $resolver;

        return $this;
    }

    /**
     * Resolver para os userRoles do usuário atual.
     * O Closure retorna array de strings (slugs dos roles).
     * Se não informado, tenta via auth()->user()->roles->pluck('slug').
     */
    public function userRoles(Closure $resolver): static
    {
        $this->userRolesResolver = $resolver;

        return $this;
    }

    /**
     * Callback para aplicar constraints adicionais na query de FlowExecution.
     * Útil para filtros de domínio (ex: filtro por role/assigned_to).
     */
    public function additionalQuery(Closure $callback): static
    {
        $this->additionalQueryCallback = $callback;

        return $this;
    }

    /**
     * Resolver para ações do modal por execução (já com URL resolvida).
     */
    public function modalActions(Closure $resolver): static
    {
        $this->modalActionsResolver = $resolver;

        return $this;
    }

    /**
     * Resolver para ações de card por execução (já com URL resolvida).
     */
    public function cardActions(Closure $resolver): static
    {
        $this->cardActionsResolver = $resolver;

        return $this;
    }

    /**
     * Resolver para links de card por execução (já com URL resolvida).
     */
    public function cardLinks(Closure $resolver): static
    {
        $this->cardLinksResolver = $resolver;

        return $this;
    }

    /**
     * Adiciona uma ou mais colunas de enriquecimento por execução.
     *
     * @param  ExecutionColumn[]  $columns
     */
    public function columns(array $columns): static
    {
        foreach ($columns as $column) {
            $this->column($column);
        }

        return $this;
    }

    public function column(ExecutionColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Constrói e retorna todos os dados do board Kanban.
     */
    public function getBoardData(): array
    {
        $workableIds = $this->resolveWorkableIds();
        $steps = $this->getWorkflowSteps();

        if (empty($workableIds)) {
            return [
                'board' => [
                    'steps' => $this->formatStepsForFrontend($steps),
                    'executions' => [],
                ],
                'groupConfigs' => $this->resolveGroupConfigs(),
                'userRoles' => $this->resolveUserRoles(),
                'filters' => ['values' => $this->getFiltersResponse()],
            ];
        }

        $executions = $this->getExecutions($workableIds);
        $enriched = $this->enrichExecutions($executions);
        $grouped = $this->groupExecutionsByStep($enriched, $steps);

        return [
            'board' => [
                'steps' => $this->formatStepsForFrontend($steps),
                'executions' => $grouped,
            ],
            'groupConfigs' => $this->resolveGroupConfigs(),
            'userRoles' => $this->resolveUserRoles(),
            'filters' => ['values' => $this->getFiltersResponse()],
        ];
    }

    protected function resolveWorkableIds(): array
    {
        if ($this->workableIdsResolver === null) {
            return [];
        }

        if (is_array($this->workableIdsResolver)) {
            return $this->workableIdsResolver;
        }

        return ($this->workableIdsResolver)() ?? [];
    }

    protected function resolveGroupConfigs(): array
    {
        if ($this->groupConfigsResolver === null) {
            return [];
        }

        return ($this->groupConfigsResolver)() ?? [];
    }

    protected function resolveUserRoles(): array
    {
        if ($this->userRolesResolver !== null) {
            return ($this->userRolesResolver)() ?? [];
        }

        $user = auth()->user();
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'roles')) {
            return $user->roles->pluck('slug')->toArray();
        }

        return [];
    }

    protected function getWorkflowSteps(): Collection
    {
        if ($this->flow !== null) {
            return $this->flow->stepTemplates()
                ->where('is_active', true)
                ->orderBy('suggested_order')
                ->get();
        }

        return FlowStepTemplate::where('is_active', true)
            ->orderBy('suggested_order')
            ->get();
    }

    protected function formatStepsForFrontend(Collection $steps): array
    {
        $arr = $steps->values()->all();
        $result = [];

        foreach ($arr as $i => $step) {
            $next = $arr[$i + 1] ?? null;
            $prev = $i > 0 ? $arr[$i - 1] : null;

            $result[] = [
                'id' => $step->id,
                'name' => $step->name,
                'slug' => $step->slug,
                'color' => $step->color,
                'description' => $step->description,
                'suggested_order' => $step->suggested_order,
                'templateNextStep' => $next ? ['id' => $next->id, 'name' => $next->name] : null,
                'templatePreviousStep' => $prev ? ['id' => $prev->id, 'name' => $prev->name] : null,
            ];
        }

        return $result;
    }

    protected function getExecutions(array $workableIds): Collection
    {
        $with = ['configStep.stepTemplate', 'stepTemplate'];

        $with['metrics'] = function ($query): void {
            $query->where('workable_type', $this->workableType)
                ->orderByDesc('calculated_at')
                ->orderByDesc('created_at');
        };

        if ($this->withDetailModal) {
            $with[] = 'configStep.participants';

            $authUserId = auth()->id();
            $with['notifications'] = function ($query) use ($authUserId): void {
                    $query->where('notifiable_type', $this->workableType)
                        ->orderByDesc('created_at');

                if ($authUserId) {
                    $query->where('user_id', (string) $authUserId);
                }
            };
        }

        $query = FlowExecution::query()
            ->with($with)
            ->where('workable_type', $this->workableType)
            ->whereIn('workable_id', $workableIds);

        // Generic filters handled by the package
        if ($this->hasFilter('user_id')) {
            $query->where('current_responsible_id', $this->getFilter('user_id'));
        }

        if ($this->hasFilter('status')) {
            $query->where('status', $this->getFilter('status'));
        }

        if ($this->getFilter('only_overdue', false)) {
            $query->whereNotNull('sla_date')
                ->where('sla_date', '<', now())
                ->whereNotIn('status', [FlowStatus::Completed, FlowStatus::Skipped]);
        }

        if (! $this->hasFilter('status') && ! $this->getFilter('show_completed', false)) {
            $query->where('status', '!=', FlowStatus::Completed);
        }

        // Domain-specific constraints (ex: assigned_to role filter)
        if ($this->additionalQueryCallback !== null) {
            ($this->additionalQueryCallback)($query);
        }

        return $query->get();
    }

    protected function enrichExecutions(Collection $executions): Collection
    {
        // Pre-load users needed for detail modal (using Laravel's auth user model)
        $usersById = collect();

        if ($this->withDetailModal) {
            $userIds = collect();

            foreach ($executions as $execution) {
                $userIds->push($execution->current_responsible_id, $execution->execution_started_by);

                if ($execution->relationLoaded('configStep') && $execution->configStep?->relationLoaded('participants')) {
                    $userIds = $userIds->merge($execution->configStep->participants->pluck('user_id'));
                }
            }

            $userIds = $userIds->filter()->unique()->values();

            if ($userIds->isNotEmpty()) {
                /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModel */
                $userModel = config('auth.providers.users.model', 'App\Models\User');
                $usersById = $userModel::query()->whereIn('id', $userIds)->get()->keyBy('id');
            }
        }

        $context = [
            'board' => $this,
            'usersById' => $usersById,
            'authUser' => auth()->user(),
        ];

        return $executions->map(function (FlowExecution $execution) use ($context) {
            $status = $execution->status instanceof FlowStatus
                ? $execution->status->value
                : (string) $execution->status;

            $authUser = $context['authUser'] ?? null;
            $abilities = $authUser ? FlowExecutionPolicy::abilities($authUser, $execution) : null;

            $data = [
                'id' => $execution->id,
                'workflow_step_template_id' => $execution->flow_step_template_id,
                'flow_config_step_id' => $execution->flow_config_step_id,
                'status' => $status,
                'status_presentation' => $this->getStatusPresentation($status),
                'current_responsible_id' => $execution->current_responsible_id,
                'execution_started_by' => $execution->execution_started_by,
                'started_at' => $execution->started_at?->toIso8601String(),
                'completed_at' => $execution->completed_at?->toIso8601String(),
                'sla_date' => $execution->sla_date?->toIso8601String(),
                'is_overdue' => $execution->sla_date
                    && $execution->sla_date->isPast()
                    && ! in_array($status, ['completed', 'skipped'], true),
                'notes' => $execution->notes,
                'paused_at' => $execution->paused_at?->toIso8601String(),
                'abilities' => $abilities,
                'action_visibility' => [
                    'start' => $abilities['can_start'] ?? false,
                    'move' => $abilities['can_move'] ?? false,
                    'pause' => $abilities['can_pause'] ?? false,
                    'resume' => $abilities['can_resume'] ?? false,
                    'assign' => $abilities['can_assign'] ?? false,
                    'abandon' => $abilities['can_abandon'] ?? false,
                    'notes' => $abilities['can_notes'] ?? false,
                ],
                'modal_actions' => [],
                'card_actions' => [],
                'card_links' => [],
            ];

            if ($this->modalActionsResolver !== null) {
                $data['modal_actions'] = ($this->modalActionsResolver)($execution, $context) ?? [];
            }

            if ($this->cardActionsResolver !== null) {
                $data['card_actions'] = ($this->cardActionsResolver)($execution, $context) ?? [];
            }

            if ($this->cardLinksResolver !== null) {
                $data['card_links'] = ($this->cardLinksResolver)($execution, $context) ?? [];
            }

            // Apply execution columns (domain enrichment)
            foreach ($this->columns as $column) {
                $merged = $column->resolve($execution, $context);

                if (! empty($merged)) {
                    $data = array_merge($data, $merged);
                }
            }

            // Detail modal data (user resolution)
            if ($this->withDetailModal) {
                $usersById = $context['usersById'];

                $data['currentResponsible'] = $execution->current_responsible_id
                    ? $usersById->get($execution->current_responsible_id)?->only('id', 'name', 'email')
                    : null;

                $data['startedBy'] = $execution->execution_started_by
                    ? $usersById->get($execution->execution_started_by)?->only('id', 'name', 'email')
                    : null;

                $participants = collect();

                if ($execution->relationLoaded('configStep') && $execution->configStep?->relationLoaded('participants')) {
                    foreach ($execution->configStep->participants as $participant) {
                        $u = $usersById->get($participant->user_id);

                        if ($u) {
                            $participants->push($u->only('id', 'name', 'email'));
                        }
                    }
                }

                $data['config'] = ['users' => $participants->toArray()];
                $data['metrics_summary'] = $this->buildMetricsSummary($execution);
                $data['notifications_summary'] = $this->buildNotificationsSummary($execution);
            } else {
                $data['currentResponsible'] = null;
                $data['startedBy'] = null;
                $data['metrics_summary'] = [
                    'count' => 0,
                    'latest' => null,
                ];
                $data['notifications_summary'] = [
                    'count' => 0,
                    'unread_count' => 0,
                    'latest' => [],
                ];
            }

            return $data;
        })->filter()->values();
    }

    protected function groupExecutionsByStep(Collection $executions, Collection $steps): array
    {
        $grouped = [];

        foreach ($steps as $step) {
            $grouped[$step->id] = $executions
                ->filter(fn ($exec) => ($exec['workflow_step_template_id'] ?? null) === $step->id)
                ->values()
                ->toArray();
        }

        return $grouped;
    }

    protected function getFiltersResponse(): array
    {
        return $this->filters;
    }

    /**
     * @return array{count: int, latest: array<string, mixed>|null}
     */
    protected function buildMetricsSummary(FlowExecution $execution): array
    {
        $metrics = $execution->relationLoaded('metrics')
            ? $execution->metrics
            : collect();

        $latest = $metrics
            ->sortByDesc(fn ($metric) => $metric->calculated_at ?? $metric->created_at)
            ->first();

        return [
            'count' => $metrics->count(),
            'latest' => $latest ? [
                'id' => (string) $latest->id,
                'total_duration_minutes' => $latest->total_duration_minutes,
                'effective_work_minutes' => $latest->effective_work_minutes,
                'estimated_duration_minutes' => $latest->estimated_duration_minutes,
                'deviation_minutes' => $latest->deviation_minutes,
                'is_on_time' => (bool) $latest->is_on_time,
                'is_rework' => (bool) $latest->is_rework,
                'rework_count' => $latest->rework_count,
                'started_at' => $latest->started_at?->toIso8601String(),
                'completed_at' => $latest->completed_at?->toIso8601String(),
                'calculated_at' => $latest->calculated_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @return array{count: int, unread_count: int, latest: array<int, array<string, mixed>>}
     */
    protected function buildNotificationsSummary(FlowExecution $execution): array
    {
        $notifications = $execution->relationLoaded('notifications')
            ? $execution->notifications
            : collect();

        $latest = $notifications
            ->sortByDesc(fn (FlowNotification $notification) => $notification->created_at)
            ->take(5)
            ->values()
            ->map(function (FlowNotification $notification): array {
                return [
                    'id' => (string) $notification->id,
                    'type' => $notification->type?->value,
                    'priority' => $notification->priority?->value,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => (bool) $notification->is_read,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            })
            ->all();

        $latestTitles = collect($latest)
            ->map(function (array $item): ?string {
                $title = $item['title'] ?? null;
                $message = $item['message'] ?? null;

                if (is_string($title) && $title !== '') {
                    return is_string($message) && $message !== ''
                        ? sprintf('• %s - %s', $title, $message)
                        : sprintf('• %s', $title);
                }

                return is_string($message) && $message !== ''
                    ? sprintf('• %s', $message)
                    : null;
            })
            ->filter()
            ->implode("\n");

        return [
            'count' => $notifications->count(),
            'unread_count' => $notifications->where('is_read', false)->count(),
            'latest' => $latest,
            'latest_titles' => $latestTitles !== '' ? $latestTitles : '—',
        ];
    }

    protected function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]) && $this->filters[$key] !== '' && $this->filters[$key] !== null;
    }

    protected function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * @return array{label: string, icon: string, class: string}
     */
    protected function getStatusPresentation(string $status): array
    {
        $config = [
            'pending' => [
                'label' => 'Pendente',
                'icon' => 'AlertCircle',
                'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            ],
            'in_progress' => [
                'label' => 'Em Andamento',
                'icon' => 'Play',
                'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            ],
            'completed' => [
                'label' => 'Concluida',
                'icon' => 'CheckCircle',
                'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            ],
            'blocked' => [
                'label' => 'Bloqueada',
                'icon' => 'XCircle',
                'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            ],
            'paused' => [
                'label' => 'Pausada',
                'icon' => 'Pause',
                'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            ],
            'skipped' => [
                'label' => 'Pulada',
                'icon' => 'XCircle',
                'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            ],
        ];

        return $config[$status] ?? [
            'label' => $status,
            'icon' => 'AlertCircle',
            'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        ];
    }
}
