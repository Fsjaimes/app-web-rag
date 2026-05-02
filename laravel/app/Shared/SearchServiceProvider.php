<?php

declare(strict_types=1);

namespace App\Shared;

use Illuminate\Support\ServiceProvider;
use App\Shared\Application\Services\BaseSearchService;
use App\Shared\Domain\Repositories\SearchRepositoryInterface;
use App\Modules\Products\Infrastructure\Database\Repositories\ProductSearchRepository;


final class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(BaseSearchService::class)
            ->needs(SearchRepositoryInterface::class)
            ->give(function ($app) {

                $route = request()->route();

                // 1️⃣ Sin ruta (CLI, jobs, tests)
                if (!$route) {
                    return null;
                }

                $routeName = $route->getName();

                // 2️⃣ Ruta sin nombre (generated::XXXX)
                if (!$routeName) {
                    return null;
                }

                // 3️⃣ No es una ruta de búsqueda
                if (!str_contains($routeName, '.search')) {
                    return null;
                }

                // 4️⃣ Resolver SOLO búsquedas
                return match ($routeName) {

                    // 🔹 PRODUCTOS (listas de precios)
                    'price_lists.search_products'
                        => $app->make(ProductSearchRepository::class),

                    default => null,
                };
            });
    }
}
