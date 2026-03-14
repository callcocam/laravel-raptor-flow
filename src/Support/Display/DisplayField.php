<?php

namespace Callcocam\LaravelRaptorFlow\Support\Display;

class DisplayField
{
    protected ?string $label = null;

    protected ?string $format = null;

    protected ?string $url = null;

    protected ?bool $external = null;

    protected ?string $variant = null;

    protected ?string $component = null;

    protected ?string $placeholder = null;

    /** @var array<int, array<string, mixed>> */
    protected array $cards = [];

    /** @var array<string, mixed> */
    protected array $meta = [];

    public function __construct(protected string $key, protected string $type = 'text') {}

    public static function make(string $key, string $type = 'text'): static
    {
        return new static($key, $type);
    }

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

    public function labelText(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function external(bool $external = true): static
    {
        $this->external = $external;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function component(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /** @param  array<int, DisplayCardItem|array<string, mixed>>  $cards */
    public function cardsItems(array $cards): static
    {
        $this->cards = array_map(
            fn (DisplayCardItem|array $card): array => $card instanceof DisplayCardItem ? $card->toArray() : $card,
            $cards,
        );

        return $this;
    }

    /** @param  array<string, mixed>  $meta */
    public function meta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'format' => $this->format,
            'url' => $this->url,
            'external' => $this->external,
            'variant' => $this->variant,
            'component' => $this->component,
            'placeholder' => $this->placeholder,
            'cards' => $this->cards ?: null,
            'meta' => $this->meta ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
