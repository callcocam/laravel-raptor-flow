<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Enums;

enum FlowParticipantRole: string
{
    case Assignee = 'assignee';
    case Observer = 'observer';
    case Approver = 'approver';
    case Reviewer = 'reviewer';

    public function label(): string
    {
        return match ($this) {
            self::Assignee => 'Responsável',
            self::Observer => 'Observador',
            self::Approver => 'Aprovador',
            self::Reviewer => 'Revisor',
        };
    }
}
