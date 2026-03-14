<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class DisplayRow
{
    /** @var array<int, array<string, mixed>> */
    protected array $fields = [];

    public static function make(): static
    {
        return new static;
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
        return [
            'fields' => $this->fields,
        ];
    }
}
