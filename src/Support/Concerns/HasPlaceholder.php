<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasPlaceholder
{
    public function placeholder(string|Closure $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }
}
