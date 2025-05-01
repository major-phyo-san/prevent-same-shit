<?php

namespace MajorPhyoSan\PreventSameShit\Console;

use Exception;

use Illuminate\Console\Command;

use MajorPhyoSan\PreventSameShit\Action\HashRecords;

class CalculateRecordHashes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prevent-same-shit:calculate-record-hashes {modelclass} {hashcolumn?} {excludes?}';

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
        $hashColumn = ($this->argument('hashcolumn'))?? 'record_hash';
        $excludes = $this->argument('excludes');
        
        $excludeColumns = [];
        if($excludes){
            $excludeColumns = explode(',',$excludes);
        }

        // ℹ️, ✅, ⚠️, ❌
        $this->line("ℹ️ Prevent Same Shit: Row hash calculation started for model: {$modelClass}");
        try{
            (new HashRecords($modelClass, $hashColumn))->execute($excludeColumns);
            $this->line("✅ Prevent Same Shit: Row hash calculation finished");
        }catch(Exception $e){
            $this->error("❌ Prevent Same Shit: {$e->getMessage()}");
        }
    }
}
