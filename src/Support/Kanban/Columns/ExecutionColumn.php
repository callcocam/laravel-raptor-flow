<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Kanban\Columns;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;

/**
 * Coluna de enriquecimento por execução.
 *
 * O closure recebe (FlowExecution $execution, array $context) e retorna um array
 * que é merged diretamente nos dados da execução enviados ao frontend.
 *
 * Exemplo de uso:
 *   ExecutionColumn::make('workable')
 *       ->resolveUsing(fn($execution, $ctx) => [
 *           'workable' => ['id' => ..., 'name' => ..., 'group_id' => ...],
 *       ])
 */
class ExecutionColumn extends KanbanColumn
{
    /**
     * Resolve e retorna um array a ser merged nos dados da execução.
     * Retorna [] se não houver resolver ou se o resolver retornar null.
     */
    public function resolve(FlowExecution $execution, array $context): array
    {
        if ($this->resolveUsing === null) {
            return [];
        }

        $result = ($this->resolveUsing)($execution, $context);

        if ($result === null) {
            return [];
        }

        return is_array($result) ? $result : [$this->name => $result];
    }
}
