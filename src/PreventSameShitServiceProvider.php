<?php

namespace MajorPhyoSan\PreventSameShit;

use Illuminate\Support\ServiceProvider;

use MajorPhyoSan\PreventSameShit\Console\CalculateRecordHashes;
use MajorPhyoSan\PreventSameShit\Console\GenerateHashColumns;

class PreventSameShitServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CalculateRecordHashes::class,
                GenerateHashColumns::class,
            ]);
        }
    }

    public function register()
    {
        // Bind your classes or publish config here
    }
}
