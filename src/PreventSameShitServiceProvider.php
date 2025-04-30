<?php

namespace MajorPhyoSan\PreventSameShit;

use Illuminate\Support\ServiceProvider;

use MajorPhyoSan\PreventSameShit\Console\CalculateRecordHashesCommand;

class PreventSameShitServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CalculateRecordHashesCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Bind your classes or publish config here
    }
}
