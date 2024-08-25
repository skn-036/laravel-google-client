<?php

namespace Skn036\Google;

use Illuminate\Support\ServiceProvider;

class GoogleClientServiceProvider extends ServiceProvider
{
    public function boot() {
        $this->publishes([
            __DIR__.'/../config/google.php' => config_path('google.php'),
        ]);
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../config/google.php', 'google');
    }
}
