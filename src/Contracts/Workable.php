<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Contracts;

interface Workable
{
    /**
     * Identificador único do workable para uso no workflow (ex.: id ou slug).
     */
    public function getWorkflowKey(): string;

    /**
     * Label para exibição no Kanban e notificações (ex.: nome da gôndola).
     */
    public function getWorkflowLabel(): string;
}
