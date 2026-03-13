<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Services\Columns;

abstract class Column
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?string $type = 'text',
    ) {}

    abstract public function render(): string;
}
