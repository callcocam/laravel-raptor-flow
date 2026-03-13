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
 * Coluna para resolver permissões e abilities por execução.
 *
 * O closure recebe (FlowExecution $execution, array $context) e deve retornar
 * um array com as chaves 'permissions' e/ou 'abilities' a serem merged
 * nos dados da execução.
 *
 * Exemplo:
 *   PermissionsColumn::make('permissions')
 *       ->resolveUsing(fn($execution, $ctx) => [
 *           'abilities' => FlowExecutionPolicy::abilities($user, $execution),
 *           'permissions' => [
 *               'can_move' => $user->can('move', $execution),
 *               'can_start_execution' => $user->can('start', $execution),
 *           ],
 *       ])
 */
class PermissionsColumn extends ExecutionColumn
{
    public function resolve(FlowExecution $execution, array $context): array
    {
        if ($this->resolveUsing === null) {
            return [
                'abilities' => null,
                'permissions' => [],
            ];
        }

        $result = ($this->resolveUsing)($execution, $context);

        return is_array($result) ? $result : [];
    }
}
