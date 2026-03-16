<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowMetric;
use Illuminate\Support\Carbon;

class RecordFlowMetricService
{
    public function recordStepTransitionMetric(
        FlowExecution $execution,
        FlowConfigStep $fromStep,
        ?FlowConfigStep $toStep = null,
        ?Carbon $transitionAt = null,
    ): ?FlowMetric {
        $transitionAt ??= now();

        if (! $execution->started_at) {
            return null;
        }

        return FlowMetric::create($this->buildStepTransitionPayload(
            $execution,
            $fromStep,
            $toStep,
            $transitionAt,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStepTransitionPayload(
        FlowExecution $execution,
        FlowConfigStep $fromStep,
        ?FlowConfigStep $toStep,
        Carbon $transitionAt,
    ): array {
        $totalDurationMinutes = (int) $execution->started_at->diffInMinutes($transitionAt);
        $pausedDurationMinutes = (int) ($execution->paused_duration_minutes ?? 0);
        $effectiveWorkMinutes = max($totalDurationMinutes - $pausedDurationMinutes, 0);

        $estimatedDurationMinutes = (int) (($execution->estimated_duration_days ?? 0) * 24 * 60);
        if ($estimatedDurationMinutes <= 0) {
            $estimatedDurationMinutes = (int) (($fromStep->estimated_duration_days ?? 0) * 24 * 60);
        }

        $deviationMinutes = $estimatedDurationMinutes > 0
            ? $effectiveWorkMinutes - $estimatedDurationMinutes
            : null;

        $isOnTime = $execution->sla_date
            ? $transitionAt->lessThanOrEqualTo($execution->sla_date)
            : true;

        return [
            'workable_type' => $execution->workable_type,
            'workable_id' => (string) $execution->workable_id,
            'flow_config_step_id' => (string) $fromStep->id,
            'flow_step_template_id' => (string) $fromStep->flow_step_template_id,
            'total_duration_minutes' => $totalDurationMinutes,
            'effective_work_minutes' => $effectiveWorkMinutes,
            'estimated_duration_minutes' => $estimatedDurationMinutes > 0 ? $estimatedDurationMinutes : null,
            'deviation_minutes' => $deviationMinutes,
            'is_on_time' => $isOnTime,
            'is_rework' => false,
            'rework_count' => 0,
            'started_at' => $execution->started_at,
            'completed_at' => $transitionAt,
            'calculated_at' => $transitionAt,
            'metadata' => [
                'from_step_id' => (string) $fromStep->id,
                'to_step_id' => $toStep?->id ? (string) $toStep->id : null,
                'paused_duration_minutes' => $pausedDurationMinutes,
                'execution_status_at_transition' => $execution->status->value,
            ],
        ];
    }
}
