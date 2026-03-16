<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Display;

use Callcocam\LaravelRaptorFlow\Support\Concerns\EvaluatesConfiguredValues;
use Callcocam\LaravelRaptorFlow\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasLabel;
use Closure;

class DisplayColumn
{
    use EvaluatesConfiguredValues;
    use FactoryPattern;
    use HasLabel;

    protected string|Closure|null $label = null;

    protected string|Closure|null $style = null;

    protected bool|Closure|null $showWhenEmpty = null;

    /** @var array<int, DisplayField|array<string, mixed>> */
    protected array $fields = [];

    public function __construct(protected string $id) {}

    public function style(string|Closure $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function addField(DisplayField|array $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    public function showWhenEmpty(bool|Closure $condition = true): static
    {
        $this->showWhenEmpty = $condition;

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

        return array_filter([
            'id' => $this->id,
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'style' => $this->evaluateConfiguredValue($this->style, $target),
            'showWhenEmpty' => $this->evaluateConfiguredValue($this->showWhenEmpty, $target),
            'fields' => $fields,
        ], fn (mixed $value): bool => $value !== null);
    }
}
