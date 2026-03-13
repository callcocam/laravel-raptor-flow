<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

/**
 * Ação personalizada com URL e label livres.
 *
 * Uso:
 *   CustomAction::make('ver-gondola')
 *       ->label('Ver Gôndola')
 *       ->icon('Eye')
 *       ->url('/gondolas/{gondola_id}')
 *       ->method('get')
 */
class CustomAction extends FlowAction
{
    public function __construct()
    {
        $this->setUp();
    }

    protected function setUp(): void {}
}
