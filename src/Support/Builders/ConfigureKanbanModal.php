<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Support\Builders;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Support\Actions\FlowAction;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplaySection;
use Callcocam\LaravelRaptorFlow\Support\Display\NotesBlock;
use Closure;

class ConfigureKanbanModal
{
    /** @var FlowAction[] */
    protected array $actions = [];

    /** @var DisplaySection[] */
    protected array $sections = [];

    /** @var NotesBlock[] */
    protected array $notes = [];

    /** @var array<array{key: string, label: string, url: string|Closure, external?: bool}> */
    protected array $links = [];

    public static function make(): static
    {
        return new static;
    }

    public function addAction(FlowAction $action): static
    {
        $this->actions[] = $action;

        return $this;
    }

    public function addSection(DisplaySection $section): static
    {
        $this->sections[] = $section;

        return $this;
    }

    public function addNote(NotesBlock $note): static
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * @param  array{key: string, label: string, url: string|Closure, external?: bool}  $link
     */
    public function addLink(array $link): static
    {
        $this->links[] = $link;

        return $this;
    }

    /**
     * @return array{sections: array<mixed>, actions: array<mixed>, links: array<mixed>, notes: array<mixed>}
     */
    public function toArray(): array
    {
        return [
            'sections' => array_map(fn (DisplaySection $section) => $section->toArray(), $this->sections),
            'actions' => array_map(fn (FlowAction $action) => $action->toArray(), $this->actions),
            'links' => $this->serializeLinks(),
            'notes' => array_map(fn (NotesBlock $note) => $note->toArray(), $this->notes),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveActionsForExecution(FlowExecution $execution): array
    {
        return array_values(array_map(
            fn (FlowAction $action) => $action->toArray($execution),
            array_filter($this->actions, fn (FlowAction $action) => $action->isVisible($execution)),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function serializeLinks(?FlowExecution $execution = null): array
    {
        return array_map(function (array $link) use ($execution) {
            $url = $link['url'];

            return [
                'key' => $link['key'],
                'label' => $link['label'],
                'url' => $url instanceof Closure ? (string) $url($execution) : $url,
                'external' => (bool) ($link['external'] ?? false),
            ];
        }, $this->links);
    }
}
