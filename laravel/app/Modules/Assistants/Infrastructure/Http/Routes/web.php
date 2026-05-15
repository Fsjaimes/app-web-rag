<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Assistants\Infrastructure\Http\Controllers\AssistantController;

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Modifique el prefijo y nombre de esta ruta, debe ser en español y en kebab-case
    Route::get('/assistants', [AssistantController::class, 'index'])->name('assistants.index');
    Route::prefix('assistants')->group(function () {
        // Route::get('/create', [AssistantController::class, 'viewCreate'])->name('assistants.viewCreate');
        // Route::get('/{uuid}/show', [AssistantController::class, 'viewShow'])->name('assistants.viewShow');
        // Route::get('/{uuid}/edit', [AssistantController::class, 'viewEdit'])->name('assistants.viewEdit');
        // Route::post('/', [AssistantController::class, 'store'])->name('assistants.store');
        // Route::put('/{uuid}', [AssistantController::class, 'update'])->name('assistants.update');
        // Route::delete('/{id}', [AssistantController::class, 'destroy'])->name('assistants.destroy');
    });
});