<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

use Closure;

/**
 * Ação genérica para o modal de detalhes do Kanban (laravel-raptor-flow).
 *
 * O URL pode conter placeholders {param} que serão resolvidos pelo frontend
 * com os dados da execução. Ex: /flow/executions/{id}/start
 */
abstract class FlowAction
{
    protected string $id = '';

    protected string $label = '';

    protected ?string $icon = null;

    protected string $method = 'post';

    protected string|Closure $url = '#';

    protected string $variant = 'outline';

    /** Status em que a ação é visível. null = sempre visível. */
    protected ?array $visibleStatuses = null;

    protected ?array $confirm = null;

    protected array $data = [];

    protected string $type = 'action';

    public static function make(string $id): static
    {
        $instance = new static;
        $instance->id = $id;

        return $instance;
    }

    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtolower($method);

        return $this;
    }

    /**
     * URL da ação. Pode conter placeholders {param} que o frontend resolve
     * com os dados da execução. Ex: /flow/executions/{id}/start
     */
    public function url(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Define em quais status da execução a ação é visível.
     * Ex: ['pending'], ['in_progress'], ['paused']
     * null = sempre visível.
     */
    public function visibleStatuses(array $statuses): static
    {
        $this->visibleStatuses = $statuses;

        return $this;
    }

    /**
     * Diálogo de confirmação antes de executar a ação.
     */
    public function confirm(string $title, string $description = ''): static
    {
        $this->confirm = [
            'title' => $title,
            'description' => $description,
        ];

        return $this;
    }

    /**
     * Dados extras enviados no corpo da requisição (POST/PATCH).
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Resolve o URL: se for Closure, chama passando o modelo; se for string, retorna direto.
     */
    protected function resolveUrl(mixed $target = null): string
    {
        if ($this->url instanceof Closure) {
            return (string) ($this->url)($target);
        }

        return $this->url;
    }

    /**
     * Serializa a ação para array (passado ao frontend via Inertia).
     *
     * @param  mixed  $target  Modelo da execução (opcional, usado para resolver closures)
     */
    public function toArray(mixed $target = null): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'icon' => $this->icon,
            'method' => $this->method,
            'url' => $this->resolveUrl($target),
            'variant' => $this->variant,
            'visibleStatuses' => $this->visibleStatuses,
            'confirm' => $this->confirm,
            'data' => $this->data,
        ];
    }
}
