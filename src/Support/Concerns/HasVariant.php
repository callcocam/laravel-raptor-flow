<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasVariant
{
    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }
}
