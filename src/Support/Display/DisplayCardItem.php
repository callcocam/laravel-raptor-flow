<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class DisplayCardItem
{
    protected ?string $label = null;

    protected ?string $format = null;

    protected ?string $icon = null;

    protected ?string $variant = null;

    public function __construct(protected string $key) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'label' => $this->label,
            'format' => $this->format,
            'icon' => $this->icon,
            'variant' => $this->variant,
        ], fn (mixed $value): bool => $value !== null);
    }
}
