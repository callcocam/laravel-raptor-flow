<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\FlowPresetStep;

class ResolvePresetStepUsersService
{
    /**
     * @return array<int, string>
     */
    public function resolveForPresetStep(FlowPresetStep $presetStep): array
    {
        if ($presetStep->relationLoaded('participants')) {
            $participantUsers = $presetStep->participants
                ->pluck('user_id')
                ->map(fn ($value) => (string) $value)
                ->filter(fn (string $value) => $value !== '')
                ->unique()
                ->values()
                ->all();

            if ($participantUsers !== []) {
                return $participantUsers;
            }
        }

        return $this->normalizeUsers($presetStep->users ?? []);
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