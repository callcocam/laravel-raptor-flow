<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Support\Builders;

use Callcocam\LaravelRaptorFlow\Models\FlowPresetStep;
use Callcocam\LaravelRaptorFlow\Services\ResolvePresetStepUsersService;

class FlowPresetStepPayloadBuilder
{
    public function __construct(
        protected ?ResolvePresetStepUsersService $resolvePresetStepUsersService = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildFromPresetStep(FlowPresetStep $presetStep, int $order): array
    {
        $resolvedUsers = $this->resolveUsers($presetStep);

        return [
            'flow_step_template_id' => (string) $presetStep->workflow_step_template_id,
            'name' => $presetStep->name ?? $presetStep->stepTemplate?->name,
            'description' => $presetStep->stepTemplate?->description,
            'order' => $order,
            'default_role_id' => $presetStep->default_role_id,
            'suggested_responsible_id' => $this->resolveSuggestedResponsibleId($presetStep, $resolvedUsers),
            'estimated_duration_days' => $presetStep->estimated_duration_days ?? $presetStep->stepTemplate?->estimated_duration_days,
            'is_required' => (bool) $presetStep->is_required,
            'is_active' => true,
            'allow_skip' => (bool) $presetStep->allow_skip,
            'auto_assign_role' => (bool) $presetStep->auto_assign_role,
            'auto_assign_user' => (bool) $presetStep->auto_assign_user,
            'users' => $resolvedUsers,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function resolveUsers(FlowPresetStep $presetStep): array
    {
        if (! $this->resolvePresetStepUsersService) {
            return collect($presetStep->users ?? [])
                ->map(fn ($value) => (string) $value)
                ->values()
                ->all();
        }

        return $this->resolvePresetStepUsersService->resolveForPresetStep($presetStep);
    }

    /**
     * @param  array<int, string>  $resolvedUsers
     */
    protected function resolveSuggestedResponsibleId(FlowPresetStep $presetStep, array $resolvedUsers): ?string
    {
        if (! empty($presetStep->suggested_responsible_id)) {
            return (string) $presetStep->suggested_responsible_id;
        }

        return $resolvedUsers[0] ?? null;
    }
}