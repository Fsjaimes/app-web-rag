<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Assistants\Infrastructure\Http\Controllers\AssistantController;

// Página del chat — requiere autenticación
Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/chat', [AssistantController::class, 'index'])->name('asistente.chat');
    });

// API del chat — accesible también para usuarios anónimos (session_id)
Route::middleware(['web'])
    ->group(function () {
        Route::post('/chat', [AssistantController::class, 'chat'])->name('asistente.preguntar');
        Route::get('/conversaciones/{uuid}/historial', [AssistantController::class, 'history'])->name('asistente.historial');
    });
