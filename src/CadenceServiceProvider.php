<?php

namespace MrNewport\LaravelCadence;

use Illuminate\Support\ServiceProvider;

class CadenceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PreviewCommand::class]);
        }
    }
}
