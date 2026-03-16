<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Services;

use Callcocam\LaravelRaptorFlow\Enums\FlowNotificationPriority;
use Callcocam\LaravelRaptorFlow\Enums\FlowNotificationType;
use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Models\FlowNotification;

class FlowNotificationService
{
    public function notifyAssigned(
        FlowExecution $execution,
        string|int|null $userId,
        ?string $title = null,
        ?string $message = null,
        FlowNotificationPriority $priority = FlowNotificationPriority::Medium,
        array $metadata = [],
    ): ?FlowNotification {
        if (! $userId) {
            return null;
        }

        return FlowNotification::create($this->buildAssignedPayload(
            $execution,
            (string) $userId,
            $title,
            $message,
            $priority,
            $metadata,
        ));
    }

    public function notifyMoved(
        FlowExecution $execution,
        string|int|null $userId,
        ?FlowConfigStep $fromStep,
        FlowConfigStep $toStep,
        array $metadata = [],
    ): ?FlowNotification {
        if (! $userId) {
            return null;
        }

        return FlowNotification::create($this->buildMovedPayload(
            $execution,
            (string) $userId,
            $fromStep,
            $toStep,
            $metadata,
        ));
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function buildAssignedPayload(
        FlowExecution $execution,
        string $userId,
        ?string $title,
        ?string $message,
        FlowNotificationPriority $priority,
        array $metadata,
    ): array {
        return [
            'user_id' => $userId,
            'notifiable_type' => $execution->workable_type,
            'notifiable_id' => (string) $execution->workable_id,
            'flow_config_step_id' => $execution->flow_config_step_id,
            'type' => FlowNotificationType::Assigned,
            'priority' => $priority,
            'title' => $title ?? 'Nova responsabilidade atribuída',
            'message' => $message ?? 'Você recebeu uma etapa para executar no workflow.',
            'link' => null,
            'is_read' => false,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function buildMovedPayload(
        FlowExecution $execution,
        string $userId,
        ?FlowConfigStep $fromStep,
        FlowConfigStep $toStep,
        array $metadata,
    ): array {
        return [
            'user_id' => $userId,
            'notifiable_type' => $execution->workable_type,
            'notifiable_id' => (string) $execution->workable_id,
            'flow_config_step_id' => $toStep->id,
            'type' => FlowNotificationType::Moved,
            'priority' => FlowNotificationPriority::Medium,
            'title' => 'Etapa movida no workflow',
            'message' => sprintf(
                'A execução foi movida de %s para %s.',
                $fromStep?->name ?? 'etapa anterior',
                $toStep->name ?? 'nova etapa',
            ),
            'link' => null,
            'is_read' => false,
            'metadata' => array_merge([
                'from_step_id' => $fromStep?->id,
                'to_step_id' => $toStep->id,
            ], $metadata),
        ];
    }
}
