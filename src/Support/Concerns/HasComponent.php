<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasComponent
{
    /**
     * Define o componente de renderização no frontend.
     *
     * Pode receber string fixa, callback ou null para ocultar o componente customizado.
     */
    public function component(string|Closure|null $component): static
    {
        $this->component = $component;

        return $this;
    }
}
