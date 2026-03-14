<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasIcon
{
    public function icon(string|Closure $icon): static
    {
        $this->icon = $icon;

        return $this;
    }
}
