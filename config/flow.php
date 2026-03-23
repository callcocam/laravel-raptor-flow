<?php

/**
 * Configuração do pacote laravel-raptor-flow.
 *
 * @see docs/plan.md
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Path das migrations (client) — obsoleto quando flow está no landlord
    |--------------------------------------------------------------------------
    |
    | Quando null, as tabelas flow ficam só no banco principal; as migrations
    | do flow devem estar em database/migrations/ da app (não em clients).
    |
    */
    'client_migrations_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Conexão de banco de dados
    |--------------------------------------------------------------------------
    |
    | Conexão usada pelas tabelas flow_*. Padrão: mesmo do banco principal (landlord).
    | Defina FLOW_DB_CONNECTION no .env para outro driver (ex.: tenant) se precisar.
    |
    */
    'connection' => env('FLOW_DB_CONNECTION', env('DB_CONNECTION')),

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
    'route_prefix' => env('FLOW_ROUTE_PREFIX', ''),
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

    /*
    |--------------------------------------------------------------------------
    | Eventos de domínio
    |--------------------------------------------------------------------------
    |
    | Permite ao app registrar um subscriber para ouvir FlowExecutionActionOccurred
    | sem alterar o package. Se disabled ou subscriber inválido, o package ignora.
    |
    */
    'events' => [
        'enabled' => false,
        'subscriber' => null,
    ],
];
