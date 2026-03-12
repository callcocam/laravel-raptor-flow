<?php

namespace Callcocam\LaravelRaptorFlow\Enums;

enum FlowAction: string
{
    case Start = 'start';
    case Move = 'move';
    case Complete = 'complete';
    case Pause = 'pause';
    case Resume = 'resume';
    case Reassign = 'reassign';
    case Skip = 'skip';
    case Abandon = 'abandon';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Iniciar',
            self::Move => 'Mover',
            self::Complete => 'Concluir',
            self::Pause => 'Pausar',
            self::Resume => 'Retomar',
            self::Reassign => 'Reatribuir',
            self::Skip => 'Pular',
            self::Abandon => 'Abandonar',
        };
    }
}
