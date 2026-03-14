<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class DisplayColumn
{
    protected ?string $label = null;

    protected ?string $style = null;

    /** @var array<int, array<string, mixed>> */
    protected array $fields = [];

    public function __construct(protected string $id) {}

    public static function make(string $id): static
    {
        return new static($id);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function addField(DisplayField|array $field): static
    {
        $this->fields[] = $field instanceof DisplayField ? $field->toArray() : $field;

        return $this;
    }

    /** @param  array<int, DisplayField|array<string, mixed>>  $fields */
    public function addFields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'style' => $this->style,
            'fields' => $this->fields,
        ], fn (mixed $value): bool => $value !== null);
    }
}
