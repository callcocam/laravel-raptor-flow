<?php

namespace Callcocam\LaravelRaptorFlow\Support\Builders;

use Callcocam\LaravelRaptorFlow\Services\ResolvePresetDefaultUsersService;
use Callcocam\LaravelRaptorFlow\Services\ResolveStepTemplateSuggestedUsersService;

class FlowConfigStepPayloadBuilder
{
    public function __construct(
        protected ?ResolveStepTemplateSuggestedUsersService $resolveStepTemplateSuggestedUsersService = null,
        protected ?ResolvePresetDefaultUsersService $resolvePresetDefaultUsersService = null,
    ) {}

    /**
     * @param  array<string, mixed>  $configData
     * @return array<string, mixed>|null
     */
    public function buildFromConfig(
        array $configData,
        int $order,
        bool $includeUsers = true,
        ?string $workableType = null,
    ): ?array
    {
        $templateId = $configData['workflow_step_template_id'] ?? $configData['flow_step_template_id'] ?? null;

        if (! $templateId) {
            return null;
        }

        $payload = [
            'flow_step_template_id' => (string) $templateId,
            'order' => $order,
            'default_role_id' => $configData['responsible_role_id'] ?? $configData['default_role_id'] ?? null,
            'estimated_duration_days' => (int) ($configData['estimated_duration_days'] ?? 2),
        ];

        $resolvedUsers = [];
        if ($includeUsers) {
            $resolvedUsers = $this->normalizeUsers($configData['users'] ?? []);

            if ($resolvedUsers === [] && $this->resolveStepTemplateSuggestedUsersService) {
                $resolvedUsers = $this->resolveStepTemplateSuggestedUsersService
                    ->resolveForTemplate((string) $templateId);
            }

            if ($resolvedUsers === [] && $this->resolvePresetDefaultUsersService) {
                $resolvedUsers = $this->resolvePresetDefaultUsersService
                    ->resolveForTemplate((string) $templateId, $workableType);
            }

            $payload['users'] = $resolvedUsers;
        }

        $suggestedResponsibleId = $this->resolveSuggestedResponsibleId($configData, $includeUsers, $resolvedUsers);
        if ($suggestedResponsibleId !== null) {
            $payload['suggested_responsible_id'] = $suggestedResponsibleId;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $configData
     */
    protected function resolveSuggestedResponsibleId(array $configData, bool $includeUsers, array $resolvedUsers): ?string
    {
        $explicitSuggested = $configData['suggested_responsible_id'] ?? null;
        if ($explicitSuggested !== null && $explicitSuggested !== '') {
            return (string) $explicitSuggested;
        }

        if (! $includeUsers) {
            return null;
        }

        return $resolvedUsers[0] ?? null;
    }

    /**
     * @param  mixed  $usersPayload
     * @return array<int, string>
     */
    protected function normalizeUsers(mixed $usersPayload): array
    {
        if (is_null($usersPayload) || $usersPayload === '') {
            return [];
        }

        if (is_string($usersPayload)) {
            $usersPayload = str_contains($usersPayload, ',')
                ? explode(',', $usersPayload)
                : [$usersPayload];
        }

        if (! is_array($usersPayload)) {
            $usersPayload = [$usersPayload];
        }

        return collect($usersPayload)
            ->map(function ($value) {
                if (is_array($value)) {
                    return $value['id'] ?? $value['value'] ?? null;
                }

                return $value;
            })
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values()
            ->all();
    }
}