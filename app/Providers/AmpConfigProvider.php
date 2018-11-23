<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AmpConfig;

class AmpConfigProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('Core', function () {
            return new AmpConfig();
        });
    }
}
