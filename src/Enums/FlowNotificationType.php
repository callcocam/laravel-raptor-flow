<?php

namespace Callcocam\LaravelRaptorFlow\Enums;

enum FlowNotificationType: string
{
    case Assigned = 'assigned';
    case Overdue = 'overdue';
    case Moved = 'moved';
    case Completed = 'completed';
    case Mentioned = 'mentioned';
    case Reminder = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Atribuído',
            self::Overdue => 'Atrasado',
            self::Moved => 'Movido',
            self::Completed => 'Concluído',
            self::Mentioned => 'Mencionado',
            self::Reminder => 'Lembrete',
        };
    }
}
