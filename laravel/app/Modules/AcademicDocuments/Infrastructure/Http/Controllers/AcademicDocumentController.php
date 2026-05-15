<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AcademicDocumentController extends Controller
{
    public function index()
    {
        return Inertia::render('AcademicDocuments/Index', [
            'items' => [],
        ]);
    }

    public function viewCreate()
    {
        return Inertia::render('AcademicDocuments/Create', []);
    }

    public function viewEdit($uuid)
    {
        return Inertia::render('AcademicDocuments/Edit', [
            'item' => null,
        ]);
    }

    public function viewShow($uuid)
    {
        return Inertia::render('AcademicDocuments/Show', [
            'item' => null,
        ]);
    }

    public function store(Request $request)
    {
        // TODO: Implementar creación
        return response()->json([]);
    }

    public function update(Request $request, $uuid)
    {
        // TODO: Implementar actualización
        return response()->json([]);
    }

    public function destroy($id)
    {
        // TODO: Implementar eliminación
        return response()->json([]);
    }
}