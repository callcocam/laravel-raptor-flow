<?php

namespace Callcocam\LaravelRaptorFlow\Policies;

use Callcocam\LaravelRaptorFlow\Contracts\FlowExecutionPolicyContract;
use Callcocam\LaravelRaptorFlow\Enums\FlowStatus;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Policy padrão context-aware: mesma validação que a app pode ter.
 * - start: execução Pending e usuário elegível (suggested_responsible, participants ou role via config).
 * - pause, resume, move, assign, abandon, notes: execução InProgress/Paused e usuário é o responsável (ou admin).
 * - Role gate: se FlowConfigStep.default_role_id estiver definida, bloqueia todos os usuários sem a role,
 *   inclusive administradores. Sem bypass.
 *
 * Crie App\Policies\FlowExecutionPolicy apenas em casos bem específicos; o padrão já cobre role e responsáveis.
 */
class FlowExecutionPolicy implements FlowExecutionPolicyContract
{
    public function start(Authenticatable $user, FlowExecution $execution): bool
    {
        if ($user === null) {
            return false;
        }

        if (! $this->userPassesRoleGate($user, $execution)) {
            return false;
        }

        if ($this->hasAdminPermission($user)) {
            return true;
        }

        if ($execution->status !== FlowStatus::Pending) {
            return false;
        }

        return $this->isEligibleToStart($user, $execution);
    }

    public function move(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    public function pause(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    public function resume(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    public function assign(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    public function abandon(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    public function notes(Authenticatable $user, FlowExecution $execution): bool
    {
        return $this->canActAsResponsible($user, $execution);
    }

    /**
     * Retorna as abilities por execução para o frontend (ex.: payload do Kanban).
     *
     * @return array{can_start: bool, can_move: bool, can_pause: bool, can_resume: bool, can_assign: bool, can_abandon: bool, can_notes: bool}
     */
    public static function abilities(Authenticatable $user, FlowExecution $execution): array
    {
        $policy = app(static::class);

        return [
            'can_start' => $policy->start($user, $execution),
            'can_move' => $policy->move($user, $execution),
            'can_pause' => $policy->pause($user, $execution),
            'can_resume' => $policy->resume($user, $execution),
            'can_assign' => $policy->assign($user, $execution),
            'can_abandon' => $policy->abandon($user, $execution),
            'can_notes' => $policy->notes($user, $execution),
        ];
    }

    protected function canActAsResponsible(Authenticatable $user, FlowExecution $execution): bool
    {
        if ($user === null) {
            return false;
        }

        if (! $this->userPassesRoleGate($user, $execution)) {
            return false;
        }

        if ($this->hasAdminPermission($user)) {
            return true;
        }

        if ($execution->status !== FlowStatus::InProgress && $execution->status !== FlowStatus::Paused) {
            return false;
        }

        $responsibleId = $execution->current_responsible_id;
        if ($responsibleId === null) {
            return false;
        }

        return (string) $responsibleId === (string) $user->getAuthIdentifier();
    }

    /**
     * Verifica se o usuário atende à role obrigatória da etapa.
     * Quando default_role_id está definido, bloqueia usuários sem a role — sem bypass de admin.
     */
    protected function userPassesRoleGate(Authenticatable $user, FlowExecution $execution): bool
    {
        $execution->loadMissing('configStep');
        $step = $execution->configStep;

        if (! $step || ! $step->default_role_id) {
            return true;
        }

        $checkRole = config('flow.policy.check_role');
        if (! is_callable($checkRole)) {
            return true;
        }

        return (bool) $checkRole($user, $step->default_role_id);
    }

    protected function isEligibleToStart(Authenticatable $user, FlowExecution $execution): bool
    {
        $execution->loadMissing('configStep.participants');
        $step = $execution->configStep;

        if (! $step) {
            return false;
        }

        $userId = (string) $user->getAuthIdentifier();

        if ($step->suggested_responsible_id && (string) $step->suggested_responsible_id === $userId) {
            return true;
        }

        if ($step->relationLoaded('participants') && $step->participants->contains('user_id', $userId)) {
            return true;
        }

        return $step->participants()->where('user_id', $userId)->exists();
    }

    protected function hasAdminPermission(Authenticatable $user): bool
    {
        if (! method_exists($user, 'can')) {
            return false;
        }

        $permission = config('flow.policy.admin_permission', 'flow.execution.admin');

        return $user->can($permission);
    }
}
