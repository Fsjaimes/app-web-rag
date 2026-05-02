<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exporta el esquema de la base de datos MySQL a un archivo SQL (solo estructura, sin datos).
 * Incluye CREATE TABLE, índices y claves foráneas. Útil para importar en ChartDB u otras herramientas.
 *
 * CÓMO EJECUTARLO:
 *   php artisan export:db-chart
 *   php artisan export:db-chart --path=mi_esquema.sql
 *
 * FLUJO (paso a paso):
 * 1. Comprueba que la conexión sea MySQL; si no, termina con error.
 * 2. Obtiene el nombre de la base de datos actual.
 * 3. Lee de information_schema la lista de tablas (solo BASE TABLE) del esquema.
 * 4. Si no hay tablas, muestra aviso y termina.
 * 5. Construye el SQL: cabecera con comentarios, SET FOREIGN_KEY_CHECKS=0, y por cada tabla:
 *    - Comentario con el nombre de la tabla.
 *    - DROP TABLE IF EXISTS para poder re-ejecutar el script.
 *    - CREATE TABLE completo (obtenido con SHOW CREATE TABLE de MySQL).
 * 6. Añade SET FOREIGN_KEY_CHECKS=1 al final.
 * 7. Escribe todo el contenido en el archivo (por defecto chartdb.sql en la raíz del proyecto).
 */
class ExportDbChart extends Command {
    protected $signature = 'export:db-chart {--path=chartdb.sql}';
    protected $description = 'Export database schema as MySQL SQL (tables, indexes, foreign keys) for ChartDB or other tools';

    public function handle() {
        // Solo permitir MySQL; el formato SQL generado es específico de MySQL
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            $this->error('SQL export is only supported for MySQL.');

            return Command::FAILURE;
        }

        // Base de datos actual y lista de tablas (solo tablas base, no vistas)
        $database = DB::connection()->getDatabaseName();
        $tables = $this->getTableNames($database);

        if (empty($tables)) {
            $this->warn('No tables found in database.');

            return Command::SUCCESS;
        }

        // Generar el SQL completo y guardarlo en el path indicado (por defecto chartdb.sql)
        $sql = $this->buildSchemaSql($database, $tables);
        $path = base_path($this->option('path'));
        file_put_contents($path, $sql);

        $this->info("Schema exported to: {$path}");
        $this->info('Import in ChartDB: use this SQL in the SQL query import option, or run it in MySQL and use ChartDB’s MySQL import.');

        return Command::SUCCESS;
    }

    /**
     * Obtiene los nombres de todas las tablas del esquema desde information_schema.
     */
    private function getTableNames(string $database): array {
        $rows = DB::select("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
                AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$database]);

        return collect($rows)->pluck('TABLE_NAME')->toArray();
    }

    /**
     * Monta el archivo SQL: cabecera, desactivar FK checks, DROP + CREATE por tabla, reactivar FK checks.
     */
    private function buildSchemaSql(string $database, array $tables): string {
        $lines = [
            '-- MySQL schema export',
            '-- Database: ' . $database,
            '-- Generated for ChartDB / SQL import (structure only, no data)',
            '',
            'SET FOREIGN_KEY_CHECKS = 0;',  // Permite crear tablas en cualquier orden (FKs sin validar temporalmente)
            '',
        ];

        foreach ($tables as $table) {
            $createTable = $this->getCreateTable($table);
            if ($createTable !== null) {
                $lines[] = '-- Table: ' . $table;
                $lines[] = 'DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`;';  // Escapar backticks en nombre
                $lines[] = $createTable . ';';
                $lines[] = '';
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Ejecuta SHOW CREATE TABLE en MySQL y devuelve la sentencia CREATE TABLE (columnas, PK, índices, FKs).
     * El resultado viene en una fila con una columna; recorremos el objeto para obtener el texto CREATE TABLE.
     */
    private function getCreateTable(string $table): ?string {
        $row = DB::selectOne('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
        if ($row === null) {
            return null;
        }

        $arr = (array) $row;
        foreach ($arr as $value) {
            if (is_string($value) && stripos($value, 'CREATE TABLE') === 0) {
                return $value;
            }
        }

        return null;
    }
}
