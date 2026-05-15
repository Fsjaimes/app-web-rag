<?php
declare(strict_types=1);

namespace App\Modules\Assistants;

use Illuminate\Support\ServiceProvider;
use App\Modules\Assistants\Domain\Repositories\AssistantRepositoryInterface;
use App\Modules\Assistants\Infrastructure\Database\Repositories\EloquentAssistantRepository;

final class AssistantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssistantRepositoryInterface::class, EloquentAssistantRepository::class);
    }
    
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Infrastructure/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');
    }
}