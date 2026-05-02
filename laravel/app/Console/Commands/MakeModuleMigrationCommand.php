<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeModuleMigrationCommand extends Command
{
    protected $signature = 'make:module:migration {table?} {module?}';
    // ejemplo de uso: crea la migración para la tabla user_attachments en el módulo users
    // php artisan make:module:migration user_attachments users
    protected $description = 'Create a new migration for a module with standard fields';

    public function handle()
    {
        // Solicitar el nombre de la tabla si no se pasa como argumento
        $table = $this->argument('table') ?? $this->ask('¿Cuál es el nombre de la tabla que desea crear?');
        $moduleInput = $this->argument('module') ?? $this->ask('¿En qué módulo debe ir la migración? (ejemplo: Users, user, product, ...)');
        
        // Validar nombre de tabla
        if (!$table || !is_string($table) || trim($table) === '') {
            $this->error('El nombre de la tabla es obligatorio.');
            return Command::FAILURE;
        }
        if (!$moduleInput || !is_string($moduleInput) || trim($moduleInput) === '') {
            $this->error('El nombre del módulo es obligatorio.');
            return Command::FAILURE;
        }

        // Normalizar el nombre de la tabla: snake_case y plural
        $tableName = Str::plural(Str::snake(trim($table)));

        // Normalizar el nombre del módulo (igual que MakeModuleCommand: Studly + plural para la carpeta)
        $moduleName = Str::studly(trim($moduleInput));
        $moduleNamePlural = Str::plural($moduleName);
        $moduleNamePlural = Str::studly($moduleNamePlural);

        // Ruta según estructura de make:module: app/Modules/{ModulePlural}/Infrastructure/Database/Migrations
        $modulePath = app_path("Modules/{$moduleNamePlural}/Infrastructure/Database/Migrations");

        if (!File::exists($modulePath)) {
            $this->error("El módulo '{$moduleNamePlural}' no existe o no tiene la carpeta de migraciones.");
            $this->line("Ruta esperada: {$modulePath}");
            $this->line('Crea el módulo primero con: php artisan make:module ' . $moduleNamePlural);
            return Command::FAILURE;
        }

        // Usar el nombre de tabla normalizado para la migración
        $migrationName = 'create_' . $tableName . '_table';
        $timestamp = date('Y_m_d_His');
        $migrationFileName = $timestamp . '_' . $migrationName . '.php';
        $fullPath = "{$modulePath}/{$migrationFileName}";

        // Estructura básica de la migración con campos estándar
        $migrationStub = <<<EOT
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    // Defina los campos de la tabla, se recomienda usar los campos estándar de la tabla, si no se necesita un campo, se puede cambiar o agregar los necesarios...
    public function up(): void {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->char('uuid', 26)->unique();
            \$table->string('code')->unique();
            \$table->string('name');
            \$table->string('description');
            \$table->text('observations')->nullable();
            \$table->char('status', 1)->default('1');
            \$table->unsignedBigInteger('created_by')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            \$table->unsignedBigInteger('updated_by')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('{$tableName}');
    }
};

// Para ejecutar solo esta migración:
// php artisan migrate --path=app/Modules/{$moduleNamePlural}/Infrastructure/Database/Migrations/{$timestamp}_create_{$tableName}_table.php

EOT;

        if (File::put($fullPath, $migrationStub)) {
            $this->info("Migración creada en el módulo '{$moduleNamePlural}': {$fullPath}");
        } else {
            $this->error("No se pudo crear la migración en {$fullPath}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}