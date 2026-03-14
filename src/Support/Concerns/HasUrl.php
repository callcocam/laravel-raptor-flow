<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

use Closure;

trait HasUrl
{
    /**
     * Define o URL do recurso/ação.
     *
     * Pode receber uma string fixa ou callback que será avaliado no momento da serialização.
     */
    public function url(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }
}
