<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments;

use Illuminate\Support\ServiceProvider;
use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;
use App\Modules\AcademicDocuments\Infrastructure\Database\Repositories\EloquentAcademicDocumentRepository;
use App\Modules\AcademicDocuments\Infrastructure\Services\AIIndexingServiceInterface;
use App\Modules\AcademicDocuments\Infrastructure\Services\FastAPIAIIndexingService;

final class AcademicDocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AcademicDocumentRepositoryInterface::class, EloquentAcademicDocumentRepository::class);
        $this->app->bind(AIIndexingServiceInterface::class, FastAPIAIIndexingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Infrastructure/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');
    }
}