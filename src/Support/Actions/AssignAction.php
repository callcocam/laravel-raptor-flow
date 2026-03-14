<?php

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

class AssignAction extends FlowAction
{
    public function __construct()
    {
        $this->id = 'assign';
        $this->label = 'Atribuir';
        $this->icon = 'UserPlus';
        $this->method = 'post';
        $this->variant = 'outline';
        $this->executionRoute('flow.execution.assign');
        $this->setUp();
    }

    protected function setUp(): void {}
}