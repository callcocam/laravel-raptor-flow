<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class ResumeAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'resume';
        $this->label = 'Retomar';
        $this->icon = 'Play';
        $this->method = 'post';
        $this->variant = 'outline';
        $this->executionRoute('flow.execution.resume');
        $this->visibleStatuses = ['paused'];
        $this->confirm = [
            'title' => 'Retomar etapa?',
            'description' => 'A etapa será retomada e ficará ativa novamente.',
        ];
        $this->component('resume-action-button');
        $this->setUp();
    }

    protected function setUp(): void {}
}
