<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — UTS Assistant
|--------------------------------------------------------------------------
|
| Las rutas funcionales del asistente están registradas en cada
| ServiceProvider de módulo (AcademicDocuments, Assistants).
| Aquí solo manejamos la raíz y el dashboard.
|
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('asistente.chat'))->name('dashboard');
        Route::get('/dashboard', fn () => redirect()->route('asistente.chat'))->name('dashboard.index');
    });
