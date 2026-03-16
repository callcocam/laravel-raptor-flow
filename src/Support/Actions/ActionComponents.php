<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Support\Actions;

final class ActionComponents
{
    public const BUTTON = 'flow-action-button';

    public const CONFIRM = 'flow-action-confirm';

    public const NOTES = 'flow-action-notes';

    public const LINK = 'flow-action-link';

    public const START = 'start-action-button';

    public const PAUSE = 'pause-action-button';

    public const RESUME = 'resume-action-button';

    public const ABANDON = 'abandon-action-button';

    public static function forActionId(string $actionId): string
    {
        return match ($actionId) {
            'start' => self::START,
            'pause' => self::PAUSE,
            'resume' => self::RESUME,
            'abandon' => self::ABANDON,
            'notes' => self::NOTES,
            default => self::BUTTON,
        };
    }

    private function __construct() {}
}