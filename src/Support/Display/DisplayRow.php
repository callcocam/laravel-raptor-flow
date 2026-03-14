<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Display;

use Callcocam\LaravelRaptorFlow\Support\Concerns\FactoryPattern;

class DisplayRow
{
    use FactoryPattern;

    /** @var array<int, DisplayField|array<string, mixed>> */
    protected array $fields = [];

    public function addField(DisplayField|array $field): static
    {
        $this->fields[] = $field;

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

    public function toArray(mixed $target = null): array
    {
        $fields = [];

        foreach ($this->fields as $field) {
            $fields[] = $field instanceof DisplayField ? $field->toArray($target) : $field;
        }

        return [
            'fields' => $fields,
        ];
    }
}
