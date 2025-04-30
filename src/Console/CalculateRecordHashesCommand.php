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
        echo("Row hash calculation started \n");
        try{
            (new CalculateRecordHashes($modelClass, $hashColumn))->execute($excludeColumns);
            echo("Row hash calculation finished \n");
        }catch(Exception $e){
            echo($e->getMessage());
        }        
    }
}
