<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Kanban\Columns;

use Closure;

/**
 * Base para todas as colunas do KanbanBoard.
 * Columns executam lógica de enriquecimento configurada pelo app consumidor.
 */
abstract class KanbanColumn
{
    protected ?Closure $resolveUsing = null;

    public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function resolveUsing(Closure $callback): static
    {
        $this->resolveUsing = $callback;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
