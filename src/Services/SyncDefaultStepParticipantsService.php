<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Enums\FlowParticipantRole;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowParticipant;

class SyncDefaultStepParticipantsService
{
    /**
     * @param  array<int, string>  $userIds
     */
    public function syncForStep(FlowConfigStep $configStep, array $userIds): void
    {
        if (! config('flow.features.sync_config_step_participants', true)) {
            return;
        }

        $normalizedUserIds = collect($userIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $participantsQuery = FlowParticipant::query()
            ->where('participable_type', FlowConfigStep::class)
            ->where('participable_id', $configStep->id);

        if ($normalizedUserIds === []) {
            $participantsQuery->delete();

            return;
        }

        $participantsQuery
            ->whereNotIn('user_id', $normalizedUserIds)
            ->delete();

        foreach ($normalizedUserIds as $userId) {
            FlowParticipant::updateOrCreate(
                [
                    'user_id' => $userId,
                    'participable_type' => FlowConfigStep::class,
                    'participable_id' => $configStep->id,
                ],
                [
                    'role_in_step' => FlowParticipantRole::Assignee,
                    'is_pre_assigned' => true,
                    'assigned_at' => now(),
                ]
            );
        }
    }
}