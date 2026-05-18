<?php

namespace App\Http\Middleware;

use App\Modules\DocumentTypes\Application\Handlers\ListDocumentTypesHandler;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly ListDocumentTypesHandler $listDocumentTypesHandler
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'documentMenuTypes' => function () use ($request): array {
                if (!$request->user()) {
                    return [];
                }
                try {
                    return $this->listDocumentTypesHandler->handle(['status' => 1])->toArray();
                } catch (\Throwable) {
                    // La conexión tenant no está disponible en este entorno (standalone).
                    return [];
                }
            },
        ]);
    }
}
