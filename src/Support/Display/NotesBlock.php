<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class NotesBlock
{
    protected string $label = 'Notas';

    protected ?string $placeholder = null;

    public function __construct(protected string $id = 'notes', protected string $url = '#') {}

    public static function make(string $id = 'notes'): static
    {
        return new static($id);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'url' => $this->url,
            'placeholder' => $this->placeholder,
        ], fn (mixed $value): bool => $value !== null);
    }
}
