<?php

namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;

class WorkflowStepNavigator
{
    public function hasNextStep(FlowConfigStep $currentStep): bool
    {
        if ($currentStep->order === null) {
            return false;
        }

        return FlowConfigStep::query()
            ->where('configurable_type', $currentStep->configurable_type)
            ->where('configurable_id', $currentStep->configurable_id)
            ->where('is_active', true)
            ->where('order', '>', (int) $currentStep->order)
            ->exists();
    }

    public function isLastStep(FlowConfigStep $currentStep): bool
    {
        return ! $this->hasNextStep($currentStep);
    }
}
