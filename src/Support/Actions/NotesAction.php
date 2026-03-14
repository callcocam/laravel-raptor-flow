<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

/**
 * Ação especial que renderiza um textarea de notas no modal.
 * O frontend identifica pelo type = 'notes'.
 */
class NotesAction extends FlowAction
{
    protected string $type = 'notes';

    protected string $placeholder = 'Adicionar notas...';

    public function __construct()
    {
        $this->id = 'notes';
        $this->label = 'Notas';
        $this->method = 'post';
        $this->executionRoute('flow.execution.notes');
        $this->defaultComponent();
        $this->setUp();
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    protected function setUp(): void {}

    public function toArray(mixed $target = null): array
    {
        return array_merge(parent::toArray($target), [
            'placeholder' => $this->placeholder,
        ]);
    }
}
