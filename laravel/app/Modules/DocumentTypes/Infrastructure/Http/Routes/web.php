<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\DocumentTypes\Infrastructure\Http\Controllers\DocumentTypeController;

Route::middleware(['web', 'auth:sanctum', 'tenant', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Modifique el prefijo y nombre de esta ruta, debe ser en español y en kebab-case
    Route::get('/tipos-documentos', [DocumentTypeController::class, 'index'])->name('document-types.index');
    Route::prefix('document-types')->group(function () {
        Route::get('/create', [DocumentTypeController::class, 'viewCreate'])->name('document-types.viewCreate');
        Route::post('/validate-prefix', [DocumentTypeController::class, 'validatePrefix'])->name('document-types.validatePrefix');
        Route::post('/', [DocumentTypeController::class, 'store'])->name('document-types.store');
        Route::get('/{uuid}/show', [DocumentTypeController::class, 'viewShow'])->name('document-types.viewShow');
        Route::get('/{uuid}/edit', [DocumentTypeController::class, 'viewEdit'])->name('document-types.viewEdit');
        Route::put('/{uuid}', [DocumentTypeController::class, 'update'])->name('document-types.update');
        Route::delete('/{uuid}', [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');
    });
});