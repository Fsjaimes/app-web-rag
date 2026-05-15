<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\AcademicDocuments\Infrastructure\Http\Controllers\AcademicDocumentController;

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Modifique el prefijo y nombre de esta ruta, debe ser en español y en kebab-case
    Route::get('/academic_documents', [AcademicDocumentController::class, 'index'])->name('academic_documents.index');
    Route::prefix('academic_documents')->group(function () {
        // Route::get('/create', [AcademicDocumentController::class, 'viewCreate'])->name('academic_documents.viewCreate');
        // Route::get('/{uuid}/show', [AcademicDocumentController::class, 'viewShow'])->name('academic_documents.viewShow');
        // Route::get('/{uuid}/edit', [AcademicDocumentController::class, 'viewEdit'])->name('academic_documents.viewEdit');
        // Route::post('/', [AcademicDocumentController::class, 'store'])->name('academic_documents.store');
        // Route::put('/{uuid}', [AcademicDocumentController::class, 'update'])->name('academic_documents.update');
        // Route::delete('/{id}', [AcademicDocumentController::class, 'destroy'])->name('academic_documents.destroy');
    });
});