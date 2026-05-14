<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function index()
    {
        return Inertia::render('Assistants/Index', [
            'items' => [],
        ]);
    }

    public function viewCreate()
    {
        return Inertia::render('Assistants/Create', []);
    }

    public function viewEdit($uuid)
    {
        return Inertia::render('Assistants/Edit', [
            'item' => null,
        ]);
    }

    public function viewShow($uuid)
    {
        return Inertia::render('Assistants/Show', [
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