<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Support\Builders;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Support\Actions\FlowAction;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplayColumn;
use Closure;

class ConfigureKanbanCard
{
    /** @var DisplayColumn[] */
    protected array $columns = [];

    /** @var FlowAction[] */
    protected array $actions = [];

    /** @var array<array{key: string, label: string, url: string|Closure, position?: string, priority?: int, external?: bool}> */
    protected array $links = [];

    public static function make(): static
    {
        return new static;
    }

    public function addColumn(DisplayColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    public function addAction(FlowAction $action): static
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * @param  array{key: string, label: string, url: string|Closure, position?: 'primary'|'secondary', priority?: int, external?: bool}  $link
     */
    public function addLink(array $link): static
    {
        $this->links[] = $link;

        return $this;
    }

    /**
     * @return array{columns: array<mixed>, links: array<mixed>}
     */
    public function toArray(): array
    {
        return [
            'columns' => array_map(fn (DisplayColumn $column) => $column->toArray(), $this->columns),
            'links' => $this->serializeLinks(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveActionsForExecution(FlowExecution $execution): array
    {
        return array_values(array_map(
            fn (FlowAction $action) => $action
                    ->target('_blank')
                    ->component('flow-action-link')->toArray($execution),
            array_filter($this->actions, fn (FlowAction $action) => $action->isVisible($execution)),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveLinksForExecution(FlowExecution $execution): array
    {
        return $this->serializeLinks($execution);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function serializeLinks(?FlowExecution $execution = null): array
    {
        $normalized = array_map(function (array $link) use ($execution) {
            $url = $link['url'];

            return [
                'key' => $link['key'],
                'label' => $link['label'],
                'url' => $url instanceof Closure ? (string) $url($execution) : $url,
                'position' => in_array($link['position'] ?? 'secondary', ['primary', 'secondary'], true)
                    ? ($link['position'] ?? 'secondary')
                    : 'secondary',
                'priority' => (int) ($link['priority'] ?? 0),
                'external' => (bool) ($link['external'] ?? false),
            ];
        }, $this->links);

        usort($normalized, function (array $left, array $right): int {
            $priorityCompare = ($left['priority'] ?? 0) <=> ($right['priority'] ?? 0);

            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? ''));
        });

        return $normalized;
    }
}
