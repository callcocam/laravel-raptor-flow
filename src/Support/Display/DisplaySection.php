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

class DisplaySection
{
    use EvaluatesConfiguredValues;
    use FactoryPattern;
    use HasLabel;

    protected string|Closure|null $label = null;

    protected int $columnSpan = 12;

    /** @var array<int, DisplayRow|array<string, mixed>> */
    protected array $rows = [];

    public function __construct(protected string $id) {}

    public function columnSpan(int $columnSpan): static
    {
        $this->columnSpan = max(1, min(12, $columnSpan));

        return $this;
    }

    public function addRow(DisplayRow|array $row): static
    {
        $this->rows[] = $row;

        return $this;
    }

    public function addField(DisplayField|array $field): static
    {
        return $this->addRow(DisplayRow::make()->addField($field));
    }

    public function toArray(mixed $target = null): array
    {
        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = $row instanceof DisplayRow ? $row->toArray($target) : $row;
        }

        return array_filter([
            'id' => $this->id,
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'columnSpan' => $this->columnSpan,
            'rows' => $rows,
        ], fn (mixed $value): bool => $value !== null);
    }
}
