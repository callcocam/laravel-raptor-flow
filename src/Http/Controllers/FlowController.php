<?php

namespace Callcocam\LaravelRaptorFlow\Http\Controllers;

use Callcocam\LaravelRaptorFlow\Models\Flow;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Expõe o recurso Flow (fluxo) por slug.
 * A aplicação usa a rota para resolver o fluxo (ex.: gerenciamento de planogramas)
 * e obter step templates, configs e execuções a partir dele.
 */
class FlowController extends Controller
{
    /**
     * Lista todos os fluxos — retorna página Vue via Inertia.
     */
    public function index(): Response
    {
        $flows = Flow::query()
            ->select('id', 'name', 'slug', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('flow/index', [
            'flows' => $flows,
        ]);
    }

    /**
     * Exibe um fluxo por slug com suas etapas — retorna página Vue via Inertia.
     * Route: GET flow/flows/{flow:slug}
     */
    public function show(Flow $flow): Response
    {
        $flow->load(['stepTemplates' => fn ($q) => $q->where('is_active', true)->orderBy('suggested_order')]);

        return Inertia::render('flow/show', [
            'flow' => [
                'id' => $flow->id,
                'name' => $flow->name,
                'slug' => $flow->slug,
                'status' => $flow->status,
                'step_templates' => $flow->stepTemplates,
            ],
        ]);
    }
}
