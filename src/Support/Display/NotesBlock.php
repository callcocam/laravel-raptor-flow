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
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasPlaceholder;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasUrl;
use Closure;

class NotesBlock
{
    use EvaluatesConfiguredValues;
    use FactoryPattern;
    use HasLabel;
    use HasPlaceholder;
    use HasUrl;

    protected string|Closure $label = 'Notas';

    protected string|Closure|null $placeholder = null;

    public function __construct(protected string $id = 'notes', protected string|Closure $url = '#') {}

    public function toArray(mixed $target = null): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'url' => $this->evaluateConfiguredValue($this->url, $target),
            'placeholder' => $this->evaluateConfiguredValue($this->placeholder, $target),
        ], fn (mixed $value): bool => $value !== null);
    }
}
