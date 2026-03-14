<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class MoveAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'move';
        $this->label = 'Mover';
        $this->icon = 'Move';
        $this->method = 'post';
        $this->variant = 'outline';
        $this->executionRoute('flow.execution.move');
        $this->setUp();
    }

    protected function setUp(): void {}
}