<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasLabel
{
    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }
}
