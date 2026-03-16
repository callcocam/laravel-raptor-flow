<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Enums;

enum FlowStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Blocked = 'blocked';
    case Skipped = 'skipped';

    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluído',
            self::Blocked => 'Bloqueado',
            self::Skipped => 'Pulado',
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
            self::Archived => 'Arquivado',
        };
    }
}
