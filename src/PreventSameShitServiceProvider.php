<?php

namespace MajorPhyoSan\PreventSameShit;

use Illuminate\Support\ServiceProvider;

use MajorPhyoSan\PreventSameShit\Console\CalculateRecordHashes;
use MajorPhyoSan\PreventSameShit\Console\CalculateRecordHashesAllModels;
use MajorPhyoSan\PreventSameShit\Console\GenerateHashColumn;
use MajorPhyoSan\PreventSameShit\Console\GenerateHashColumnsAllModels;

class PreventSameShitServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CalculateRecordHashes::class,
                CalculateRecordHashesAllModels::class,
                GenerateHashColumn::class,
                GenerateHashColumnsAllModels::class,
            ]);
        }
    }

    public function register()
    {
        // Bind your classes or publish config here
    }
}
