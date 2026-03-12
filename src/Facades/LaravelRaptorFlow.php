<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Callcocam\LaravelRaptorFlow\LaravelRaptorFlow
 */
class LaravelRaptorFlow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Callcocam\LaravelRaptorFlow\LaravelRaptorFlow::class;
    }
}
