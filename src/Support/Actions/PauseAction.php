<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class PauseAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'pause';
        $this->label = 'Pausar';
        $this->icon = 'Pause';
        $this->method = 'post';
        $this->variant = 'outline';
        $this->visibleStatuses = ['in_progress'];
        $this->setUp();
    }

    protected function setUp(): void {}
}
