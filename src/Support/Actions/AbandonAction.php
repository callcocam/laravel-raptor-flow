<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class AbandonAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'abandon';
        $this->label = 'Abandonar';
        $this->icon = 'LogOut';
        $this->method = 'post';
        $this->variant = 'destructive';
        $this->executionRoute('flow.execution.abandon');
        $this->visibleStatuses = ['in_progress'];
        $this->confirm = [
            'title' => 'Abandonar etapa?',
            'description' => 'Você vai liberar a responsabilidade desta etapa. Outro usuário poderá assumir.',
        ];
        $this->component('abandon-action-button');
        $this->setUp();
    }

    protected function setUp(): void {}
}
