<?php

namespace MajorPhyoSan\PreventSameShit\Console;

use Exception;

use Illuminate\Console\Command;

use MajorPhyoSan\PreventSameShit\Maintenance\CalculateRecordHashes;

class CalculateRecordHashesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prevent-same-shit:calculate-record-hashes-command {modelclass} {excludes?} {hashcolumn?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command to generate SHA256 hashes for each model record, (record_hash or a designated hash column must be present )";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $modelClass = $this->argument('modelclass');
        $excludes = $this->argument('excludes');
        $hashColumn = ($this->argument('hashcolumn'))?? 'record_hash';
        $excludeColumns = [];
        if($excludes){
            $excludeColumns = explode(',',$excludes);
        }

        // ℹ️, ✅, ⚠️, ❌
        $this->line("ℹ️ Prevent Same Shit: Row hash calculation started for model: {$modelClass}");
        try{
            (new CalculateRecordHashes($modelClass, $hashColumn))->execute($excludeColumns);
            $this->line("✅ Prevent Same Shit: Row hash calculation finished");
        }catch(Exception $e){
            $this->error("❌ Prevent Same Shit: {$e->getMessage()}");
        }
    }
}
