<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes;

use Illuminate\Support\ServiceProvider;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;
use App\Modules\DocumentTypes\Infrastructure\Database\Repositories\EloquentDocumentTypeRepository;

final class DocumentTypesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentTypeRepositoryInterface::class, EloquentDocumentTypeRepository::class);
    }
    
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Infrastructure/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');
    }
}