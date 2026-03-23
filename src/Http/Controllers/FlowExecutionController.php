<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptorFlow\Http\Controllers;

use Callcocam\LaravelRaptorFlow\Models\FlowConfigStep;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Callcocam\LaravelRaptorFlow\Services\FlowManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller; 
use Illuminate\Validation\ValidationException;

/**
 * Controller genérico para ações em FlowExecution (start, move, pause, resume, assign, abandon, notes).
 * Autorização via Policy (padrão no pacote; sobrescreva com App\Policies\FlowExecutionPolicy).
 */
class FlowExecutionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected FlowManager $flowManager) {}

    /**
     * Inicia uma execução pendente (transição para Em andamento).
     */
    public function start(FlowExecution $execution): RedirectResponse
    {
        $this->authorize('start', $execution);

        $user = auth()->id();
        if (! $user) {
            return redirect()->back()->with('error', 'Usuário não autenticado.');
        }

        try {
            $this->flowManager->startPendingExecution($execution, $user);

            return redirect()->back()->with('success', 'Workflow iniciado com sucesso.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Move a execução para outra etapa (drag & drop no Kanban).
     */
    public function move(Request $request, FlowExecution $execution): RedirectResponse
    {
        $this->authorize('move', $execution);

        $validated = $request->validate([
            'to_step_id' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $toStep = $this->resolveDestinationStep($execution, $validated['to_step_id']); // Tenta resolver por ID de configuração ou por template+configurável
        if (! $toStep) {
            return redirect()->back()->with('error', 'Etapa de destino inválida para este workflow.');
        }

        $user = auth()->id();

        try {
            $this->flowManager->moveExecution(
                $execution,
                $toStep,
                $user,
                $validated['notes'] ?? null
            );

            return redirect()->back()->with('success', 'Item movido com sucesso.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->with('error', 'Não foi possível mover o item.');
        }
    }

    /**
     * Pausa a execução.
     */
    public function pause(FlowExecution $execution): RedirectResponse
    {
        $this->authorize('pause', $execution);

        $user = auth()->id();

        try {
            $this->flowManager->pauseExecution($execution, $user);

            return redirect()->back()->with('success', 'Execução pausada.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Retoma a execução pausada.
     */
    public function resume(FlowExecution $execution): RedirectResponse
    {
        $this->authorize('resume', $execution);

        $user = auth()->id();

        try {
            $this->flowManager->resumeExecution($execution, $user);

            return redirect()->back()->with('success', 'Execução retomada.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Reatribui a execução para outro usuário.
     */
    public function assign(Request $request, FlowExecution $execution): RedirectResponse
    {
        $this->authorize('assign', $execution);

        $validated = $request->validate([
            'user_id' => ['required'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->id();
        if (! $user) {
            return redirect()->back()->with('error', 'Usuário não autenticado.');
        }

        try {
            $this->flowManager->assignExecution(
                $execution,
                $user,
                $validated['user_id'],
                $validated['notes'] ?? null
            );

            return redirect()->back()->with('success', 'Execução reatribuída com sucesso.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Responsável atual abandona a etapa.
     */
    public function abandon(FlowExecution $execution): RedirectResponse
    {
        $this->authorize('abandon', $execution);

        $user = auth()->id();
        if (! $user) {
            return redirect()->back()->with('error', 'Usuário não autenticado.');
        }

        try {
            $this->flowManager->abandonExecution($execution, $user);

            return redirect()->back()->with('success', 'Responsabilidade liberada com sucesso.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Finaliza a execução (transição para Concluído).
     */
    public function finish(FlowExecution $execution): RedirectResponse
    {
        $this->authorize('finish', $execution);

        $user = auth()->id();
        if (! $user) {
            return redirect()->back()->with('error', 'Usuário não autenticado.');
        }

        try {
            $this->flowManager->finishExecution($execution, $user);

            return redirect()->back()->with('success', 'Execução finalizada com sucesso.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Atualiza as notas da execução.
     */
    public function notes(Request $request, FlowExecution $execution): RedirectResponse
    {
        $this->authorize('notes', $execution);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->flowManager->updateExecutionNotes($execution, $validated['notes']);

            return redirect()->back()->with('success', 'Notas atualizadas.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    protected function resolveDestinationStep(FlowExecution $execution, string $toStepId): ?FlowConfigStep
    {
        $stepByConfigId = FlowConfigStep::query()->find($toStepId);

        if ($stepByConfigId) {
            return $stepByConfigId;
        }

        $execution->loadMissing('configStep');
        $fromStep = $execution->configStep;

        if (! $fromStep) {
            return null;
        }

        return FlowConfigStep::query()
            ->where('configurable_type', $fromStep->configurable_type)
            ->where('configurable_id', $fromStep->configurable_id)
            ->where('flow_step_template_id', $toStepId)
            ->where('is_active', true)
            ->first();
    }
}
