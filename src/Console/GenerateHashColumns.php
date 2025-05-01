<?php

namespace MajorPhyoSan\PreventSameShit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GenerateHashColumns extends Command
{
    protected $signature = 'prevent-same-shit:generate-hash-columns {column=record_hash}';

    protected $description = 'Auto-generate migrations to add a column to models if not present';

    // ℹ️, ✅, ⚠️, ❌
    public function handle()
    {
        $modelsPath = app_path('Models');
        $column = $this->argument('column');

        $this->line("ℹ️ Prevent Same Shit: Hash columns generation started");
        if (!File::exists($modelsPath)) {
            $this->error("❌ Prevent Same Shit: Models directory not found at $modelsPath, terminating");
            Log::critical("Prevent Same Shit: Models directory not found at $modelsPath");
            return;
        }

        $modelFiles = File::files($modelsPath);
        $created = 0;

        foreach ($modelFiles as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClass = "App\\Models\\{$className}";

            if (!class_exists($fullClass)) {
                continue;
            }

            $model = new $fullClass;

            if (!method_exists($model, 'getTable')) {
                $this->warn("⚠️ Prevent Same Shit: Skipping $fullClass (no getTable method)");
                Log::warning("Prevent Same Shit: Skipping $fullClass (no getTable method)");
                continue;
            }

            $table = $model->getTable();

            if (!Schema::hasTable($table)) {
                $this->warn("⚠️ Prevent Same Shit: Skipping $table (table does not exist)");
                Log::warning("Prevent Same Shit: Skipping $table (table does not exist)");
                continue;
            }

            if (Schema::hasColumn($table, $column)) {
                $this->line("ℹ️ Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
                Log::info("Prevent Same Shit: Skipping {$table} (column '{$column}' already exists)");
                continue;
            }

            // Generate timestamped migration file name
            $timestamp = now()->format('Y_m_d_His');
            $migrationName = "add_{$column}_to_{$table}_table";
            $fileName = database_path("migrations/{$timestamp}_{$migrationName}.php");

            $content = $this->generateMigrationContent($table, $column);
            File::put($fileName, $content);

            $this->info("ℹ️ Prevent Same Shit: Migration created: {$fileName}");
            Log::info("Prevent Same Shit: Migration created: {$fileName}");
            $created++;
        }

        $this->info("✅ Prevent Same Shit: Finished, {$created} migration(s) created.");
        Log::info("Prevent Same Shit: Finished, {$created} migration(s) created.");
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

