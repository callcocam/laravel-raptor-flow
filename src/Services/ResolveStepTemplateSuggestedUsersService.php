<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\FlowStepTemplate;

class ResolveStepTemplateSuggestedUsersService
{
    /**
     * @return array<int, string>
     */
    public function resolveForTemplate(string $flowStepTemplateId): array
    {
        if (! config('flow.features.resolve_template_suggested_users', true)) {
            return [];
        }

        $template = FlowStepTemplate::query()->find($flowStepTemplateId);
        if (! $template) {
            return [];
        }

        $metadata = is_array($template->metadata) ? $template->metadata : [];

        return $this->normalizeUsers(
            $metadata['suggested_users'] ?? $metadata['users'] ?? [],
        );
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