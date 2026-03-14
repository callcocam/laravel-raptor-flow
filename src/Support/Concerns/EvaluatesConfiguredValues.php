<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptorFlow\Support\Concerns;

trait EvaluatesConfiguredValues
{
    use EvaluatesClosures;

    protected function evaluateConfiguredValue(mixed $value, mixed $target = null): mixed
    {
        if ($value === null) {
            return null;
        }

        $namedInjections = [
            'target' => $target,
            'record' => $target,
            'execution' => $target,
            'model' => $target,
        ];

        $typedInjections = [];

        if (is_object($target)) {
            $typedInjections[$target::class] = $target;
        }

        return $this->evaluate($value, $namedInjections, $typedInjections);
    }
}
