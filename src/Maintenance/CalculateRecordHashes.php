<?php

namespace MajorPhyoSan\PreventSameShit\Maintenance;

use Exception;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CalculateRecordHashes
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

        $model = new $modelClass;
        if(!$model){
            throw new Exception("No morph map or specified model not found, terminating\n");
            return false;
        }
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);
        if(!in_array($this->hashColumn, $columns)){
            // echo("Specified hashed column not found, terminating\n");
            throw new Exception("Specified hashed column not found, terminating\n");
            return false;
        }

        $excludedFromHash = ['id',$hashColumn,'created_at','updated_at'];
        foreach ($exclude as $column) {
            array_push($excludedFromHash, $column);
        }

        echo("Excluded columns\n");
        print_r($excludedFromHash);

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
                }catch(Exception $e){
                    echo("Duplicate row detected at id: {$model->id}\n");
                    Log::warning($e->getMessage(), ["model" => $model]);
                    $model->delete();
                    echo("Duplicate row deleted\n");
                    Log::info('Duplicate row deleted');
                }
            }
        });

        return true;
    }
}
