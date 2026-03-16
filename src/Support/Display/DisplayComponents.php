<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Support\Display;

final class DisplayComponents
{
    public const FIELD = 'flow-display-field';

    public const NOTE_BLOCK = 'flow-display-note-block';

    public const TEXT = 'flow-display-text';

    public const LABEL = 'flow-display-label';

    public const TEXTAREA = 'flow-display-textarea';

    public const DATE = 'flow-display-date';

    public const DATETIME = 'flow-display-datetime';

    public const BADGE = 'flow-display-badge';

    public const LINK = 'flow-display-link';

    public const CARDS = 'flow-display-cards';

    public const TIMELINE = 'flow-display-timeline';

    public const SELECT_USERS = 'flow-display-select-users';

    public const CUSTOM = 'flow-display-custom';

    public static function forType(string $type): string
    {
        return match ($type) {
            'text' => self::TEXT,
            'label' => self::LABEL,
            'textarea' => self::TEXTAREA,
            'date' => self::DATE,
            'datetime' => self::DATETIME,
            'badge' => self::BADGE,
            'link' => self::LINK,
            'cards' => self::CARDS,
            'timeline' => self::TIMELINE,
            'selectUsers' => self::SELECT_USERS,
            default => self::CUSTOM,
        };
    }

    private function __construct() {}
}