<?php

use Callcocam\LaravelRaptorFlow\Http\Controllers\FlowController;
use Callcocam\LaravelRaptorFlow\Http\Controllers\FlowExecutionController;
use Callcocam\LaravelRaptorFlow\Models\FlowExecution;
use Illuminate\Support\Facades\Route;

/*
| Fluxo (Flow): recurso que agrupa step templates, configs e execuções.
| Ex.: "Gerenciamento de planogramas" — acessível por slug.
*/
Route::get('flows', [FlowController::class, 'index'])->name('flow.index');
Route::get('flows/{flow:slug}', [FlowController::class, 'show'])->name('flow.show');

/*
| Rotas de ações em execuções (flow). O contexto "qual fluxo" (gôndolas, campanhas, etc.)
| é definido pela aplicação (ex.: index Kanban). Estas rotas são genéricas por execution id.
*/
Route::bind('execution', function (string $value) {
    return FlowExecution::query()->findOrFail($value);
});

Route::prefix('executions/{execution}')->name('flow.execution.')->group(function () {
    Route::post('start', [FlowExecutionController::class, 'start'])->name('start');
    Route::post('move', [FlowExecutionController::class, 'move'])->name('move');
    Route::post('pause', [FlowExecutionController::class, 'pause'])->name('pause');
    Route::post('resume', [FlowExecutionController::class, 'resume'])->name('resume');
    Route::post('assign', [FlowExecutionController::class, 'assign'])->name('assign');
    Route::post('abandon', [FlowExecutionController::class, 'abandon'])->name('abandon');
    Route::post('notes', [FlowExecutionController::class, 'notes'])->name('notes');
    Route::post('finish', [FlowExecutionController::class, 'finish'])->name('finish');
});
