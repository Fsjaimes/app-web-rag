<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new DDD module with complete folder structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->argument('name');
        $moduleNamePlural = Str::plural($moduleName);
        $moduleNameSingular = Str::singular($moduleName);
        
        // Normalizar nombres
        $moduleName = Str::studly($moduleName);
        $moduleNamePlural = Str::studly($moduleNamePlural);
        $moduleNameSingular = Str::studly($moduleNameSingular);
        $moduleNameLower = Str::snake($moduleName);
        $moduleNamePluralLower = Str::snake($moduleNamePlural);
        $moduleNameSingularLower = Str::snake($moduleNameSingular);

        $basePath = app_path("Modules/{$moduleNamePlural}");
        
        // Verificar si el módulo ya existe
        if (File::exists($basePath)) {
            $this->error("Module '{$moduleNamePlural}' already exists!");
            return Command::FAILURE;
        }
        
        $this->info("Creating DDD module: {$moduleNamePlural}");
        
        // Crear estructura de carpetas
        $this->createDirectoryStructure($basePath);
        
        // Crear ServiceProvider
        $this->createServiceProvider($basePath, $moduleNamePlural, $moduleNameLower, $moduleNameSingular);
        
        // Crear archivos .gitkeep en carpetas vacías
        // $this->createGitkeepFiles($basePath);
        
        // Archivos referenciados en ServiceProvider (Domain + Infrastructure)
        $this->createEntity($basePath, $moduleNamePlural, $moduleNameSingular);
        $this->createRepositoryInterface($basePath, $moduleNamePlural, $moduleNameSingular);
        $this->createEloquentModel($basePath, $moduleNamePlural, $moduleNameSingular, $moduleNamePluralLower);
        $this->createRepositoryImplementation($basePath, $moduleNamePlural, $moduleNameSingular, $moduleNameLower, $moduleNamePluralLower);
        $this->createNotFoundException($basePath, $moduleNamePlural, $moduleNameSingular);
        
        // Controlador con métodos vacíos (nombre en singular)
        $this->createController($basePath, $moduleNamePlural, $moduleNameSingular, $moduleNamePluralLower);
        
        // Crear archivo de rutas (con rutas que apuntan al controlador)
        $this->createRoutesFile($basePath, $moduleNamePlural, $moduleNameLower, $moduleNamePluralLower, $moduleNameSingular);
        
        // Solo Index.vue en resources/js/Pages/{ModuleName}
        $this->createVueIndexPage($moduleNamePlural, $moduleNamePluralLower);
        
        // Registrar el ServiceProvider del módulo en config/app.php
        $this->registerProviderInConfig($moduleNamePlural);

        // Ejecutar php artisan optimize
        $this->info("Ejecutando php artisan optimize...");
        $this->call('optimize');
        $this->newLine();

        // Mostrar mensaje de éxito
        $this->info("Módulo '{$moduleNamePlural}' creado correctamente!");
        $this->newLine();

        // Mostrar mensaje de carga
        $this->info("Intente cargar la vista en http://localhost:8300/{$moduleNamePluralLower} -> esto varía dependiendo del puerto configurado");
        $this->newLine();

        // Mostrar mensaje de desarrollo
        $this->info("Sigue developing...");
        $this->line("Módulo en la carpeta app/Modules/{$moduleNamePlural}/ y en resources/js/Pages/{$moduleNamePlural}/");
        $this->newLine();
        
        return Command::SUCCESS;
    }
    
    /**
     * Create the complete directory structure
     */
    private function createDirectoryStructure(string $basePath): void
    {
        $directories = [
            // Application Layer
            "{$basePath}/Application/Commands",
            "{$basePath}/Application/DTOs",
            "{$basePath}/Application/Handlers",
            "{$basePath}/Application/Queries",
            "{$basePath}/Application/Services",
            
            // Domain Layer
            "{$basePath}/Domain/Entities",
            "{$basePath}/Domain/Exceptions",
            "{$basePath}/Domain/Repositories",
            "{$basePath}/Domain/Services",
            "{$basePath}/Domain/ValueObjects",
            
            // Infrastructure Layer
            "{$basePath}/Infrastructure/Database/Migrations",
            "{$basePath}/Infrastructure/Database/Models",
            "{$basePath}/Infrastructure/Database/Repositories",
            "{$basePath}/Infrastructure/Database/Seeders",
            "{$basePath}/Infrastructure/Http/Controllers",
            "{$basePath}/Infrastructure/Http/Requests",
            "{$basePath}/Infrastructure/Http/Resources",
            "{$basePath}/Infrastructure/Http/Routes",
        ];
        
        foreach ($directories as $directory) {
            File::makeDirectory($directory, 0755, true);
            $this->line("Created: {$directory}");
        }
    }
    
    /**
     * Create ServiceProvider
     */
    private function createServiceProvider(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameLower,
        string $moduleNameSingular
    ): void {
        $serviceProviderName = "{$moduleNamePlural}ServiceProvider";
        $repositoryInterface = "{$moduleNameSingular}RepositoryInterface";
        $repositoryImplementation = "Eloquent{$moduleNameSingular}Repository";
        
        $content = <<<PHP
<?php
declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural};

use Illuminate\\Support\\ServiceProvider;
use App\\Modules\\{$moduleNamePlural}\\Domain\\Repositories\\{$repositoryInterface};
use App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Database\\Repositories\\{$repositoryImplementation};

final class {$serviceProviderName} extends ServiceProvider
{
    public function register(): void
    {
        \$this->app->bind({$repositoryInterface}::class, {$repositoryImplementation}::class);
    }
    
    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__.'/Infrastructure/Http/Routes/web.php');
        \$this->loadMigrationsFrom(__DIR__.'/Infrastructure/Database/Migrations');
    }
}
PHP;
        
        File::put("{$basePath}/{$serviceProviderName}.php", $content);
        $this->line("Created: {$serviceProviderName}.php");
    }
    
    /**
     * Create .gitkeep files in empty directories
     */
    private function createGitkeepFiles(string $basePath): void
    {
        $gitkeepDirectories = [
            "{$basePath}/Application/Commands",
            "{$basePath}/Application/DTOs",
            "{$basePath}/Application/Handlers",
            "{$basePath}/Application/Queries",
            "{$basePath}/Domain/Entities",
            "{$basePath}/Domain/Exceptions",
            "{$basePath}/Domain/Repositories",
            "{$basePath}/Domain/Services",
            "{$basePath}/Domain/ValueObjects",
            "{$basePath}/Infrastructure/Database/Migrations",
            "{$basePath}/Infrastructure/Database/Models",
            "{$basePath}/Infrastructure/Database/Repositories",
            "{$basePath}/Infrastructure/Database/Seeders",
            "{$basePath}/Infrastructure/Http/Controllers",
            "{$basePath}/Infrastructure/Http/Requests",
            "{$basePath}/Infrastructure/Http/Resources",
            "{$basePath}/Infrastructure/Http/Routes",
        ];
        
        foreach ($gitkeepDirectories as $directory) {
            File::put("{$directory}/.gitkeep", '');
        }
    }
    
    /**
     * Create basic example files
     */
    private function createBasicFiles(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular,
        string $moduleNameLower,
        string $moduleNamePluralLower
    ): void {
        $this->createRepositoryInterface($basePath, $moduleNamePlural, $moduleNameSingular);
        $this->createRepositoryImplementation($basePath, $moduleNamePlural, $moduleNameSingular, $moduleNameLower, $moduleNamePluralLower);
        $this->createEntity($basePath, $moduleNamePlural, $moduleNameSingular);
        $this->createEloquentModel($basePath, $moduleNamePlural, $moduleNameSingular, $moduleNamePluralLower);
        $this->createNotFoundException($basePath, $moduleNamePlural, $moduleNameSingular);
        $this->createRoutesFile($basePath, $moduleNamePlural, $moduleNameLower, $moduleNamePluralLower, $moduleNameSingular);
    }
    
    /**
     * Create Repository Interface
     */
    private function createRepositoryInterface(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular
    ): void {
        $interfaceName = "{$moduleNameSingular}RepositoryInterface";
        $content = <<<PHP
<?php
declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Domain\\Repositories;

use App\\Modules\\{$moduleNamePlural}\\Domain\\Entities\\{$moduleNameSingular};

interface {$interfaceName}
{
    public function findById(string \$id): ?{$moduleNameSingular};
    
    public function findAll(): array;
    
    public function save({$moduleNameSingular} \${$moduleNameSingular}): void;
    
    public function delete(string \$id): void;
}
PHP;
        
        File::put("{$basePath}/Domain/Repositories/{$interfaceName}.php", $content);
        $this->line("Created: Domain/Repositories/{$interfaceName}.php");
    }
    
    /**
     * Create Repository Implementation
     */
    private function createRepositoryImplementation(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular,
        string $moduleNameLower,
        string $moduleNamePluralLower
    ): void {
        $repositoryName = "Eloquent{$moduleNameSingular}Repository";
        $interfaceName = "{$moduleNameSingular}RepositoryInterface";
        $modelName = $moduleNameSingular;
        $tableName = $moduleNamePluralLower;
        
        $content = <<<PHP
<?php
declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Database\\Repositories;

use App\\Modules\\{$moduleNamePlural}\\Domain\\Repositories\\{$interfaceName};
use App\\Modules\\{$moduleNamePlural}\\Domain\\Entities\\{$moduleNameSingular};
use App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Database\\Models\\{$modelName};

class {$repositoryName} implements {$interfaceName}
{
    public function findById(string \$id): ?{$moduleNameSingular}
    {
        \$model = {$modelName}::find(\$id);
        
        if (!\$model) {
            return null;
        }
        
        return \$this->toDomain(\$model);
    }
    
    public function findAll(): array
    {
        return {$modelName}::all()
            ->map(fn(\$model) => \$this->toDomain(\$model))
            ->toArray();
    }
    
    public function save({$moduleNameSingular} \${$moduleNameSingular}): void
    {
        \$model = {$modelName}::find(\${$moduleNameSingular}->id()) ?? new {$modelName}();
        
        // TODO: Mapear propiedades de la entidad al modelo
        // \$model->name = \${$moduleNameSingular}->name();
        
        \$model->save();
    }
    
    public function delete(string \$id): void
    {
        {$modelName}::destroy(\$id);
    }
    
    private function toDomain({$modelName} \$model): {$moduleNameSingular}
    {
        // TODO: Implementar conversión de Model a Entity
        throw new \RuntimeException('Implementar conversión de Model a Entity en toDomain()');
    }
}
PHP;
        
        File::put("{$basePath}/Infrastructure/Database/Repositories/{$repositoryName}.php", $content);
        $this->line("Created: Infrastructure/Database/Repositories/{$repositoryName}.php");
    }
    
    /**
     * Create Domain Entity
     */
    private function createEntity(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular
    ): void {
        $content = <<<PHP
<?php
declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Domain\\Entities;

final class {$moduleNameSingular}
{
    private function __construct(
        private string \$id,
        // TODO: Agregar propiedades de la entidad
    ) {}
    
    public static function create(string \$id): self
    {
        return new self(\$id);
    }
    
    public function id(): string
    {
        return \$this->id;
    }
    
    // TODO: Agregar métodos de la entidad
}
PHP;
        
        File::put("{$basePath}/Domain/Entities/{$moduleNameSingular}.php", $content);
        $this->line("Created: Domain/Entities/{$moduleNameSingular}.php");
    }
    
    /**
     * Create Eloquent Model
     */
    private function createEloquentModel(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular,
        string $moduleNamePluralLower
    ): void {
        $modelName = $moduleNameSingular;
        $tableName = $moduleNamePluralLower;
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Database\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class {$modelName} extends Model
{
    use SoftDeletes;

    protected \$table = '{$tableName}';
    public \$incrementing = true;
    protected \$keyType = 'int';

    protected \$fillable = [
        'id',
        // TODO: Agregar campos fillable
    ];

    protected \$casts = [
        // TODO: Agregar casts si es necesario
        // 'created_at' => 'datetime',
        // 'updated_at' => 'datetime',
    ];
}
PHP;
        
        File::put("{$basePath}/Infrastructure/Database/Models/{$modelName}.php", $content);
        $this->line("Created: Infrastructure/Database/Models/{$modelName}.php");
    }
    
    /**
     * Create NotFoundException
     */
    private function createNotFoundException(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular
    ): void {
        $exceptionName = "{$moduleNameSingular}NotFoundException";
        $content = <<<PHP
<?php
declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Domain\\Exceptions;

use Exception;

final class {$exceptionName} extends Exception
{
    public static function withId(string \$id): self
    {
        return new self("{$moduleNameSingular} with ID {\$id} not found");
    }
}
PHP;
        
        File::put("{$basePath}/Domain/Exceptions/{$exceptionName}.php", $content);
        $this->line("Created: Domain/Exceptions/{$exceptionName}.php");
    }
    
    /**
     * Create Controller with empty methods
     */
    private function createController(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameSingular,
        string $moduleNamePluralLower
    ): void {
        $controllerName = "{$moduleNameSingular}Controller";
        $routePrefix = $moduleNamePluralLower;
        $inertiaFolder = $moduleNamePlural;

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use Inertia\\Inertia;
use Illuminate\\Http\\Request;

class {$controllerName} extends Controller
{
    public function index()
    {
        return Inertia::render('{$inertiaFolder}/Index', [
            'items' => [],
        ]);
    }

    public function viewCreate()
    {
        return Inertia::render('{$inertiaFolder}/Create', []);
    }

    public function viewEdit(\$uuid)
    {
        return Inertia::render('{$inertiaFolder}/Edit', [
            'item' => null,
        ]);
    }

    public function viewShow(\$uuid)
    {
        return Inertia::render('{$inertiaFolder}/Show', [
            'item' => null,
        ]);
    }

    public function store(Request \$request)
    {
        // TODO: Implementar creación
        return response()->json([]);
    }

    public function update(Request \$request, \$uuid)
    {
        // TODO: Implementar actualización
        return response()->json([]);
    }

    public function destroy(\$id)
    {
        // TODO: Implementar eliminación
        return response()->json([]);
    }
}
PHP;

        File::put("{$basePath}/Infrastructure/Http/Controllers/{$controllerName}.php", $content);
        $this->line("Created: Infrastructure/Http/Controllers/{$controllerName}.php");
    }

    /**
     * Create Routes file
     */
    private function createRoutesFile(
        string $basePath,
        string $moduleNamePlural,
        string $moduleNameLower,
        string $moduleNamePluralLower,
        string $moduleNameSingular
    ): void {
        $controllerName = "{$moduleNameSingular}Controller";
        $routePrefix = $moduleNamePluralLower;
        $routeNamePrefix = $moduleNamePluralLower;

        $content = <<<PHP
<?php
declare(strict_types=1);

use Illuminate\\Support\\Facades\\Route;
use App\\Modules\\{$moduleNamePlural}\\Infrastructure\\Http\\Controllers\\{$controllerName};

Route::middleware(['web', 'auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Modifique el prefijo y nombre de esta ruta, debe ser en español y en kebab-case
    Route::get('/{$routePrefix}', [{$controllerName}::class, 'index'])->name('{$routeNamePrefix}.index');
    Route::prefix('{$routePrefix}')->group(function () {
        // Route::get('/create', [{$controllerName}::class, 'viewCreate'])->name('{$routeNamePrefix}.viewCreate');
        // Route::get('/{uuid}/show', [{$controllerName}::class, 'viewShow'])->name('{$routeNamePrefix}.viewShow');
        // Route::get('/{uuid}/edit', [{$controllerName}::class, 'viewEdit'])->name('{$routeNamePrefix}.viewEdit');
        // Route::post('/', [{$controllerName}::class, 'store'])->name('{$routeNamePrefix}.store');
        // Route::put('/{uuid}', [{$controllerName}::class, 'update'])->name('{$routeNamePrefix}.update');
        // Route::delete('/{id}', [{$controllerName}::class, 'destroy'])->name('{$routeNamePrefix}.destroy');
    });
});
PHP;

        File::put("{$basePath}/Infrastructure/Http/Routes/web.php", $content);
        $this->line("Created: Infrastructure/Http/Routes/web.php");
    }

    /**
     * Crear solo Index.vue en resources/js/Pages/{ModuleName}
     */
    private function createVueIndexPage(string $moduleNamePlural, string $moduleNamePluralLower): void
    {
        $pagesPath = resource_path('js/Pages/' . $moduleNamePlural);
        if (!File::exists($pagesPath)) {
            File::makeDirectory($pagesPath, 0755, true);
        }

        $routeNamePrefix = $moduleNamePluralLower;
        $pageTitle = $moduleNamePlural;
        $componentName = $moduleNamePlural . 'Index';

        $content = <<<VUE
<script>
import Layout from '@/Layouts/main.vue';
import PageHeader from '@/Components/page-header.vue';

export default {
    name: '{$componentName}',
    components: {
        Layout,
        PageHeader,
    },
    props: {
        items: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            searchQuery: '',
        };
    },
};
</script>

<template>
    <Layout>
        <PageHeader title="{$pageTitle}" pageTitle="Gestión" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm">
                                <h5 class="card-title mb-0">{$pageTitle}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">Contenido del módulo {$pageTitle}. Implementar según requerimientos.</p>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
VUE;
        File::put("{$pagesPath}/Index.vue", $content);
        $this->line("Created: resources/js/Pages/{$moduleNamePlural}/Index.vue");
    }

    /**
     * Registrar el ServiceProvider del módulo en config/app.php
     */
    private function registerProviderInConfig(string $moduleNamePlural): void
    {
        $configPath = config_path('app.php');
        $content = File::get($configPath);

        $providerClass = "App\\Modules\\{$moduleNamePlural}\\{$moduleNamePlural}ServiceProvider::class,";
        $lineToAdd = "        {$providerClass}";

        $marker = "        // Módulos (SAT)";
        if (str_contains($content, $marker)) {
            $content = str_replace(
                $marker,
                $lineToAdd . "\n        " . trim($marker),
                $content
            );
        } else {
            // Solo el primer `])->toArray(),` tras `providers`; no usar str_replace global
            // porque `aliases` cierra con la misma cadena y duplicaría el provider.
            $providersKey = "'providers' => ServiceProvider::defaultProviders()->merge([";
            $providersPos = strpos($content, $providersKey);
            if ($providersPos === false) {
                $this->warn('No se encontró providers en config/app.php. Regístralo manualmente.');
                return;
            }

            $closing = "    ])->toArray(),";
            $insertPos = strpos($content, $closing, $providersPos);
            if ($insertPos === false) {
                $this->warn('No se encontró el cierre del array providers en config/app.php. Regístralo manualmente.');
                return;
            }

            $replacement = "        {$providerClass}\n" . $closing;
            $content = substr($content, 0, $insertPos) . $replacement . substr($content, $insertPos + strlen($closing));
        }

        File::put($configPath, $content);
        $this->line("Registered provider in config/app.php: {$moduleNamePlural}ServiceProvider");
    }
}

