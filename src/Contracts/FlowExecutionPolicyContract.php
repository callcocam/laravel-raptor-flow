<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts;

use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contrato para autorização de ações em FlowExecution.
 * A aplicação pode criar App\Policies\FlowExecutionPolicy implementando
 * esta interface (ou apenas os métodos que precisar) e registrar em um
 * ServiceProvider: Gate::policy(FlowExecution::class, \App\Policies\FlowExecutionPolicy::class).
 * Assim você usa suas regras (roles, permissions, etc.) sem depender do Raptor.
 */
interface FlowExecutionPolicyContract
{
    public function start(Authenticatable $user, FlowExecution $execution): bool;

    public function move(Authenticatable $user, FlowExecution $execution): bool;

    public function pause(Authenticatable $user, FlowExecution $execution): bool;

    public function resume(Authenticatable $user, FlowExecution $execution): bool;

    public function assign(Authenticatable $user, FlowExecution $execution): bool;

    public function abandon(Authenticatable $user, FlowExecution $execution): bool;

    public function notes(Authenticatable $user, FlowExecution $execution): bool;

    public function finish(Authenticatable $user, FlowExecution $execution): bool;
}
