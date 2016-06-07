<?php

namespace GISwrapper\Providers;

use Illuminate\Support\ServiceProvider;

class Lumen extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('GIS', function() {
            return new LumenFactory();
        });
    }
}