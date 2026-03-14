<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

use Callcocam\LaravelRaptorFlow\Support\Concerns\EvaluatesConfiguredValues;
use Callcocam\LaravelRaptorFlow\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasFormat;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasComponent;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasPlaceholder;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasUrl;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasVariant;
use Closure;

class DisplayField
{
    use EvaluatesConfiguredValues;
    use FactoryPattern;
    use HasComponent;
    use HasFormat;
    use HasPlaceholder;
    use HasUrl;
    use HasVariant;

    protected string|Closure|null $label = null;

    protected string|Closure|null $format = null;

    protected string|Closure|null $url = null;

    protected ?bool $external = null;

    protected string|Closure|null $variant = null;

    protected string|Closure|null $component = null;

    protected string|Closure|null $placeholder = null;

    /** @var array<int, DisplayCardItem|array<string, mixed>> */
    protected array $cards = [];

    /** @var array<string, mixed> */
    protected array $meta = [];

    public function __construct(protected string $key, protected string $type = 'text') {}

    public static function text(string $key): static
    {
        return static::make($key, 'text');
    }

    public static function label(string $key): static
    {
        return static::make($key, 'label');
    }

    public static function date(string $key): static
    {
        return static::make($key, 'date');
    }

    public static function datetime(string $key): static
    {
        return static::make($key, 'datetime');
    }

    public static function badge(string $key): static
    {
        return static::make($key, 'badge');
    }

    public static function link(string $key, string $url): static
    {
        return static::make($key, 'link')->url($url);
    }

    /** @param  array<int, DisplayCardItem|array<string, mixed>>  $cards */
    public static function cards(string $key, array $cards): static
    {
        return static::make($key, 'cards')->cardsItems($cards);
    }

    public static function timeline(string $key): static
    {
        return static::make($key, 'timeline');
    }

    public static function selectUsers(string $key): static
    {
        return static::make($key, 'selectUsers');
    }

    public function labelText(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function external(bool $external = true): static
    {
        $this->external = $external;

        return $this;
    }

    public function defaultComponent(): static
    {
        return $this->component(DisplayComponents::forType($this->type));
    }

    /** @param  array<int, DisplayCardItem|array<string, mixed>>  $cards */
    public function cardsItems(array $cards): static
    {
        $this->cards = $cards;

        return $this;
    }

    /** @param  array<string, mixed>  $meta */
    public function meta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function toArray(mixed $target = null): array
    {
        $cards = [];

        foreach ($this->cards as $card) {
            $cards[] = $card instanceof DisplayCardItem ? $card->toArray($target) : $card;
        }

        return array_filter([
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'format' => $this->evaluateConfiguredValue($this->format, $target),
            'url' => $this->evaluateConfiguredValue($this->url, $target),
            'external' => $this->external,
            'variant' => $this->evaluateConfiguredValue($this->variant, $target),
            'component' => $this->evaluateConfiguredValue($this->component, $target),
            'placeholder' => $this->evaluateConfiguredValue($this->placeholder, $target),
            'cards' => $cards ?: null,
            'meta' => $this->meta ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
