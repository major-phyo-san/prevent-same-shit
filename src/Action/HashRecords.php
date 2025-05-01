<?php

namespace MajorPhyoSan\PreventSameShit\Action;

use Exception;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class HashRecords
{
    protected string $modelClass;
    protected string $hashColumn;

    public function __construct(string $modelClass, string $hashColumn = 'record_hash')
    {
        $this->modelClass = $modelClass;
        $this->hashColumn = $hashColumn;
    }

    public function execute(array $exclude = [])
    {
        // Resolve model via morph map if available
        $modelClass = Relation::getMorphedModel($this->modelClass) ?? $this->modelClass;
        $hashColumn = $this->hashColumn;

        if (!class_exists($modelClass) || !is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            Log::error("Prevent Same Shit: Invalid model class => {$modelClass} or enforce morph map not implemented, terminating");
            throw new Exception("Invalid model class => {$modelClass} or enforce morph map not implemented, terminating");
        }
        
        $model = new $modelClass;        
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);
        if(!in_array($this->hashColumn, $columns)){
            Log::error("Prevent Same Shit: Specified hashed column not found, terminating");
            throw new Exception("Specified hashed column not found, terminating");
            return false;
        }

        $excludedFromHash = ['id',$hashColumn,'created_at','updated_at'];
        foreach ($exclude as $column) {
            array_push($excludedFromHash, $column);
        }

        echo("ℹ️ Prevent Same Shit: Excluded columns\n");
        print_r($excludedFromHash);
        Log::info("Prevent Same Shit: Excluded columns", $excludedFromHash);

        $modelClass::chunk(100, function ($models) use ($hashColumn, $excludedFromHash) {
            foreach ($models as $model) {
                $data = $model->attributesToArray();
                foreach ($excludedFromHash as $column) {
                    unset($data[$column]);
                }
                ksort($data);
                $payload = json_encode($data);

                $hmac = hash_hmac('sha256', $payload, Config::get('app.key'));

                try{
                    if ($model->getAttribute($hashColumn) !== $hmac) {
                        $model->setAttribute($hashColumn, $hmac);
                        $model->saveQuietly();
                    }
                    echo("✅ Prevent Same Shit: Row at id {$model->id} OK\n");
                    Log::info("Prevent Same Shit: Row at id {$model->id} OK");
                }catch(Exception $e){
                    Log::warning("Prevent Same Shit: Duplicate row detected at id: {$model->id}");
                    echo("⚠️ Prevent Same Shit: Duplicate row detected at id: {$model->id}\n");
                    $model->delete();
                    echo("ℹ️ Prevent Same Shit: Duplicate row deleted\n");
                    Log::info('Prevent Same Shit: Duplicate row deleted');
                }
            }
        });

        return true;
    }
}
