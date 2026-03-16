<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\FlowPreset;
use Callcocam\LaravelRaptorFlow\Models\FlowPresetStep;

class ResolvePresetDefaultUsersService
{
    public function __construct(
        protected ?ResolvePresetStepUsersService $resolvePresetStepUsersService = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function resolveForTemplate(string $flowStepTemplateId, ?string $workableType = null): array
    {
        if (! config('flow.features.resolve_preset_default_users', false)) {
            return [];
        }

        $preset = $this->resolveDefaultPreset($workableType);
        if (! $preset) {
            return [];
        }

        $presetStep = $this->resolvePresetStep($preset, $flowStepTemplateId);

        if (! $presetStep) {
            return [];
        }

        if ($this->resolvePresetStepUsersService) {
            return $this->resolvePresetStepUsersService->resolveForPresetStep($presetStep);
        }

        return $this->normalizeUsers($presetStep->users ?? []);
    }

    protected function resolveDefaultPreset(?string $workableType): ?FlowPreset
    {
        return FlowPreset::resolveDefaultFor($workableType);
    }

    protected function resolvePresetStep(FlowPreset $preset, string $flowStepTemplateId): ?FlowPresetStep
    {
        if ($preset->relationLoaded('steps')) {
            return $preset->steps
                ->firstWhere('workflow_step_template_id', $flowStepTemplateId);
        }

        return $preset->steps()
            ->where('workflow_step_template_id', $flowStepTemplateId)
            ->first();
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