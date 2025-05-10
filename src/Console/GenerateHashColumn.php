<?php

namespace MajorPhyoSan\PreventSameShit\Console;

use Exception;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GenerateHashColumn extends Command
{
    protected $signature = 'prevent-same-shit:generate-hash-column {modelclass} {column=record_hash}';

    protected $description = 'Auto-generate a migration to add a hash column to a model if not present';

    // ℹ️, ✅, ⚠️, ❌
    public function handle()
    {
        $column = $this->argument('column');

        $this->line("ℹ️ Prevent Same Shit: Hash column generation started");

        $modelClassArg = $this->argument('modelclass');
        $modelClass = Relation::getMorphedModel($modelClassArg) ?? $modelClassArg;
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            $this->error("❌ Prevent Same Shit: Invalid model class => {$modelClass} or enforce morph map not implemented, terminating");
            Log::error("Prevent Same Shit: Invalid model class => {$modelClass} or enforce morph map not implemented, terminating");
            return;
        }
        $this->line("ℹ️ Prevent Same Shit: Row hash calculation started for model: {$modelClass}");
        $model = new $modelClass;
        if (!method_exists($model, 'getTable')) {
            $this->warn("⚠️ Prevent Same Shit: Skipping {$model} (no getTable method)");
            Log::warning("Prevent Same Shit: Skipping {$model} (no getTable method)");
            return;
        }
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->warn("⚠️ Prevent Same Shit: Skipping $table (table does not exist)");
            Log::warning("Prevent Same Shit: Skipping $table (table does not exist)");
            return;
        }

        if (Schema::hasColumn($table, $column)) {
            $this->line("ℹ️ Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
            Log::info("Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
            return;
        }

        // Generate timestamped migration file name
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "add_{$column}_to_{$table}_table";
        $fileName = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $content = $this->generateMigrationContent($table, $column);
        File::put($fileName, $content);

        $this->info("ℹ️ Prevent Same Shit: Migration created: {$fileName}");
        Log::info("Prevent Same Shit: Migration created: {$fileName}");

        $this->info("✅ Prevent Same Shit: Finished, 1 migration created.");
        Log::info("Prevent Same Shit: Finished, 1 migration created.");

        // $modelFiles = File::files($modelsPath);
        // $created = 0;

        // foreach ($modelFiles as $file) {
        //     $className = pathinfo($file, PATHINFO_FILENAME);
        //     $fullClass = "App\\Models\\{$className}";

        //     if (!class_exists($fullClass)) {
        //         continue;
        //     }

        //     $model = new $fullClass;

        //     if (!method_exists($model, 'getTable')) {
        //         $this->warn("⚠️ Prevent Same Shit: Skipping $fullClass (no getTable method)");
        //         Log::warning("Prevent Same Shit: Skipping $fullClass (no getTable method)");
        //         continue;
        //     }

        //     $table = $model->getTable();

        //     if (!Schema::hasTable($table)) {
        //         $this->warn("⚠️ Prevent Same Shit: Skipping $table (table does not exist)");
        //         Log::warning("Prevent Same Shit: Skipping $table (table does not exist)");
        //         continue;
        //     }

        //     if (Schema::hasColumn($table, $column)) {
        //         $this->line("ℹ️ Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
        //         Log::info("Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
        //         continue;
        //     }

        //     // Generate timestamped migration file name
        //     $timestamp = now()->format('Y_m_d_His');
        //     $migrationName = "add_{$column}_to_{$table}_table";
        //     $fileName = database_path("migrations/{$timestamp}_{$migrationName}.php");

        //     $content = $this->generateMigrationContent($table, $column);
        //     File::put($fileName, $content);

        //     $this->info("ℹ️ Prevent Same Shit: Migration created: {$fileName}");
        //     Log::info("Prevent Same Shit: Migration created: {$fileName}");
        //     $created++;
        // }

        // $this->info("✅ Prevent Same Shit: Finished, {$created} migration(s) created.");
        // Log::info("Prevent Same Shit: Finished, {$created} migration(s) created.");
    }

    private function generateMigrationContent($table, $column): string
    {
        $className = Str::studly("add_{$column}_to_{$table}_table");

        $generatorString = "<?php
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;
        return new class extends Migration
        {
            public function up(): void
            {
                Schema::table('$table', function (Blueprint \$table) {
                    \$table->string('$column')->unique()->nullable()->after('id');
                    });
                }

            public function down(): void
            {
                Schema::table('$table', function (Blueprint \$table) {
                    \$table->dropColumn('$column');
                });
            }
        };";

    return $generatorString;
    }
}

