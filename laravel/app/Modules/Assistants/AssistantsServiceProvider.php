<?php

declare(strict_types=1);

namespace App\Modules\Assistants;

use Illuminate\Support\ServiceProvider;
use App\Modules\Assistants\Domain\Repositories\AssistantRepositoryInterface;
use App\Modules\Assistants\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Assistants\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Assistants\Infrastructure\Database\Repositories\EloquentAssistantRepository;
use App\Modules\Assistants\Infrastructure\Database\Repositories\EloquentConversationRepository;
use App\Modules\Assistants\Infrastructure\Database\Repositories\EloquentMessageRepository;
use App\Modules\Assistants\Infrastructure\Services\AIAssistantServiceInterface;
use App\Modules\Assistants\Infrastructure\Services\FastAPIAIAssistantService;

final class AssistantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssistantRepositoryInterface::class, EloquentAssistantRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, EloquentConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);
        $this->app->bind(AIAssistantServiceInterface::class, FastAPIAIAssistantService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Infrastructure/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');
    }
}