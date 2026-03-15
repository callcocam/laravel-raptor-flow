<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Actions;

use Callcocam\LaravelRaptorFlow\Support\Concerns\EvaluatesConfiguredValues;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasComponent;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasIcon;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasLabel;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasUrl;
use Callcocam\LaravelRaptorFlow\Support\Concerns\HasVariant;
use Closure;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Ação genérica para o modal de detalhes do Kanban (laravel-raptor-flow).
 *
 * O URL pode conter placeholders {param} que serão resolvidos pelo frontend
 * com os dados da execução. Ex: /flow/executions/{id}/start
 */
abstract class FlowAction
{
    use EvaluatesConfiguredValues;
    use HasComponent;
    use HasIcon;
    use HasLabel;
    use HasUrl;
    use HasVariant;

    protected string $id = '';

    protected string|Closure $label = '';

    protected string|Closure|null $icon = null;

    protected string $method = 'post';

    protected string|Closure $url = '#';

    protected string|Closure $variant = 'outline';

    protected string|Closure|null $component = ActionComponents::BUTTON;

    protected ?array $confirm = null;

    protected array $data = [];

    protected bool|Closure $visible = true;

    protected string $type = 'action';

    protected string $executionPlaceholder = '{id}';

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

    public function method(string $method): static
    {
        $this->method = strtolower($method);

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

    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function isVisible(mixed $target = null): bool
    {
        return (bool) $this->evaluateConfiguredValue($this->visible, $target);
    }

    public function defaultComponent(): static
    {
        return $this->component(ActionComponents::forActionId($this->id));
    }

    public function buttonComponent(): static
    {
        return $this->component(ActionComponents::BUTTON);
    }

    public function confirmComponent(): static
    {
        return $this->component(ActionComponents::CONFIRM);
    }

    public function notesComponent(): static
    {
        return $this->component(ActionComponents::NOTES);
    }

    public function linkComponent(): static
    {
        return $this->component(ActionComponents::LINK);
    }

    /**
     * Define URL da ação baseada em rota nomeada com placeholder da execução.
     */
    protected function executionRoute(string $action): static
    {
        if (! Route::has($action)) {
            throw new RuntimeException("Rota nomeada '$action' não encontrada. Verifique se a rota existe e se o nome está correto.");
        }

        $route = Route::getRoutes()->getByName($action);
        $parameterNames = $route?->parameterNames() ?? ['execution'];

        $parameters = [];
        $markers = [];

        foreach ($parameterNames as $parameterName) {
            $marker = "__FLOW_ACTION_{$parameterName}__";
            $parameters[$parameterName] = $marker;
            $markers[$marker] = $this->executionPlaceholder;
        }

        $generated = route($action, $parameters);
        $this->url = strtr($generated, $markers);

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
            'label' => $this->evaluateConfiguredValue($this->label, $target),
            'icon' => $this->evaluateConfiguredValue($this->icon, $target),
            'method' => $this->method,
            'url' => $this->resolveUrl($target),
            'variant' => $this->evaluateConfiguredValue($this->variant, $target),
            'confirm' => $this->confirm,
            'data' => $this->data,
            'component' => $this->evaluateConfiguredValue($this->component, $target),
        ];
    }
}
