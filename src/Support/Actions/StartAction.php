<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class StartAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'start';
        $this->label = 'Iniciar';
        $this->icon = 'Play';
        $this->method = 'post';
        $this->variant = 'default';
        $this->executionRoute('flow.execution.start');
        $this->visibleStatuses = ['pending'];
        $this->confirm = [
            'title' => 'Iniciar etapa?',
            'description' => 'A etapa será iniciada e ficará ativa.',
        ];
        $this->component('start-action-button');
        $this->setUp();
    }

    protected function setUp(): void {}
}
