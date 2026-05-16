<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\AcademicDocuments\Infrastructure\Http\Controllers\AcademicDocumentController;

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::prefix('documentos-academicos')->group(function () {
            Route::get('/', [AcademicDocumentController::class, 'index'])->name('documentos-academicos.index');
            Route::post('/', [AcademicDocumentController::class, 'store'])->name('documentos-academicos.subir');
            Route::delete('/{uuid}', [AcademicDocumentController::class, 'destroy'])->name('documentos-academicos.eliminar');
        });
    });
