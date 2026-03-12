<?php

/**
 * Configuração do pacote laravel-raptor-flow.
 *
 * @see docs/plan.md
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Path das migrations (client)
    |--------------------------------------------------------------------------
    |
    | Definido em tempo de execução pelo ServiceProvider quando as migrations
    | do flow rodam por cliente (database/migrations/clients). Use este path
    | junto com database/migrations/clients em ClientMigrationService.
    | Alternativa: publique com --tag=raptor-flow-client-migrations para
    | copiar as migrations para database/migrations/clients/.
    |
    */
    'client_migrations_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Conexão de banco de dados
    |--------------------------------------------------------------------------
    |
    | Conexão usada pelas tabelas flow_*. Em aplicações multi-tenant (ex.: Plannerate)
    | costuma ser a conexão do client. Pode ser null para usar a conexão default.
    |
    */
    'connection' => env('FLOW_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Prefixo das tabelas
    |--------------------------------------------------------------------------
    |
    | Todas as tabelas do pacote usam este prefixo. Padrão: flow_
    |
    */
    'table_prefix' => env('FLOW_TABLE_PREFIX', 'flow_'),

    /*
    |--------------------------------------------------------------------------
    | Registrar CRUDs Raptor
    |--------------------------------------------------------------------------
    |
    | Se true e o pacote callcocam/laravel-raptor estiver instalado, o pacote
    | registra os CRUDs de FlowStepTemplate e FlowPreset no Raptor.
    |
    */
    'register_raptor_cruds' => env('FLOW_REGISTER_RAPTOR_CRUDS', true),

    /*
    |--------------------------------------------------------------------------
    | Rotas de execução (start, move, pause, resume, assign, abandon, notes)
    |--------------------------------------------------------------------------
    |
    | Prefixo das rotas: flow/executions/{execution}/start, move, etc.
    | Middleware aplicado: web + auth (ajuste em route_middleware se precisar).
    |
    */
    'route_prefix' => env('FLOW_ROUTE_PREFIX', 'flow'),
    'route_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Policy (context-aware)
    |--------------------------------------------------------------------------
    |
    | admin_permission: permissão que permite bypass (ex.: flow.execution.admin).
    | check_role: callable(Authenticatable $user, ?string $roleId): bool para
    |   verificar se o usuário tem a role da etapa. Se null, não considera role
    |   (apenas suggested_responsible_id e participants). No app: publicar config
    |   e definir: 'check_role' => fn ($user, $roleId) => $user->roles->contains('id', $roleId),
    |
    */
    'policy' => [
        'admin_permission' => 'flow.execution.admin',
        'check_role' => null,
    ],
];
