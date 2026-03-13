<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\Types;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\ExecutionColumn;

/**
 * Coluna especializada para resolver dados do workable (modelo de domínio)
 * associado à execução.
 *
 * O closure recebe (FlowExecution $execution, array $context) e deve retornar
 * um array com os dados do workable. A chave no array de execução será o nome
 * da coluna (padrão: 'workable').
 *
 * Exemplo:
 *   WorkableColumn::make('workable')
 *       ->resolveUsing(fn($execution, $ctx) => [
 *           'id' => $gondola->id,
 *           'name' => $gondola->name,
 *           'group_id' => $gondola->planogram_id,
 *       ])
 */
class WorkableColumn extends ExecutionColumn
{
    public function resolve(FlowExecution $execution, array $context): array
    {
        if ($this->resolveUsing === null) {
            return [];
        }

        $result = ($this->resolveUsing)($execution, $context);

        if ($result === null) {
            return [];
        }

        // If the resolver returns a flat array of workable data,
        // wrap it in the column's name. Otherwise merge directly.
        if (is_array($result) && !isset($result[0]) && !array_key_exists($this->name, $result)) {
            return [$this->name => $result];
        }

        return is_array($result) ? $result : [$this->name => $result];
    }
}
