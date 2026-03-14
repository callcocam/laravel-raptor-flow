<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class DisplaySection
{
    protected ?string $label = null;

    protected int $columnSpan = 12;

    /** @var array<int, array<string, mixed>> */
    protected array $rows = [];

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

    public function columnSpan(int $columnSpan): static
    {
        $this->columnSpan = max(1, min(12, $columnSpan));

        return $this;
    }

    public function addRow(DisplayRow|array $row): static
    {
        $this->rows[] = $row instanceof DisplayRow ? $row->toArray() : $row;

        return $this;
    }

    public function addField(DisplayField|array $field): static
    {
        return $this->addRow(DisplayRow::make()->addField($field));
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'columnSpan' => $this->columnSpan,
            'rows' => $this->rows,
        ], fn (mixed $value): bool => $value !== null);
    }
}
