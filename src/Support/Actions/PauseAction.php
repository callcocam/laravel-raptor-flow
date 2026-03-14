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
        $this->executionRoute('flow.execution.pause');
        $this->confirm = [
            'title' => 'Pausar etapa?',
            'description' => 'A etapa ficará pausada e você continuará responsável por ela. Você pode retomar a etapa depois.',
        ];
        $this->defaultComponent();
        $this->setUp();
    }

    protected function setUp(): void {}
}
