<?php

namespace MajorPhyoSan\PreventSameShit\Console;

use Exception;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use MajorPhyoSan\PreventSameShit\Action\HashRecords;

class CalculateRecordHashesAllModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prevent-same-shit:calculate-record-hashes-all-models {includes?} {excludes?} {hashcolumn?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command to generate SHA256 hashes for each record of all models, (record_hash or a designated hash column must be present )";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $modelsPath = app_path('Models');
        if (!File::exists($modelsPath)) {
            $this->error("❌ Prevent Same Shit: Models directory not found at $modelsPath, terminating");
            Log::critical("Prevent Same Shit: Models directory not found at $modelsPath");
            return;
        }
        // $modelClass = $this->argument('modelclass');
        $hashColumn = ($this->argument('hashcolumn'))?? 'record_hash';
        $includes = $this->argument('includes');
        $excludes = $this->argument('excludes');

        $includedColumns = [];
        if($includes){
            $includedColumns = explode(',',$includes);
        }

        $excludeColumns = [];
        if($excludes){
            $excludeColumns = explode(',',$excludes);
        }

        // ℹ️, ✅, ⚠️, ❌
        $modelFiles = File::files($modelsPath);
        foreach ($modelFiles as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClass = "App\\Models\\{$className}";
            if (!class_exists($fullClass)) {
                continue;
            }
            $modelClass = $fullClass;
            $this->line("ℹ️ Prevent Same Shit: Row hash calculation started for model: {$modelClass}");
            try{
                (new HashRecords($modelClass, $hashColumn))->execute($includedColumns, $excludeColumns);
                $this->line("✅ Prevent Same Shit: Row hash calculation finished");
            }catch(Exception $e){
                $this->error("❌ Prevent Same Shit: {$e->getMessage()}");
            }
        }
    }
}
