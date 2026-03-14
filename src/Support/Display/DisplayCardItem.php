<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Display;

use Callcocam\LaravelRaptorFlow\Support\Concerns\EvaluatesConfiguredValues;
use Callcocam\LaravelRaptorFlow\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasFormat;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasIcon;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasLabel;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasVariant;
use Closure;

class DisplayCardItem
{
    use EvaluatesConfiguredValues;
    use FactoryPattern;
    use HasFormat;
    use HasIcon;
    use HasLabel;
    use HasVariant;

    protected string|Closure|null $label = null;

    protected string|Closure|null $format = null;

    protected string|Closure|null $icon = null;

    protected string|Closure|null $variant = null;

    public function __construct(protected string $key) {}

    public function toArray(mixed $target = null): array
    {
        return array_filter([
            'key' => $this->key,
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'format' => $this->evaluateConfiguredValue($this->format, $target),
            'icon' => $this->evaluateConfiguredValue($this->icon, $target),
            'variant' => $this->evaluateConfiguredValue($this->variant, $target),
        ], fn (mixed $value): bool => $value !== null);
    }
}
