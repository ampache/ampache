<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LocalplayService;

class LocalplayServiceProvider extends ServiceProvider
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
        app()->singleton('LocalplayService', function () {
            return new LocalplayService($type);
        });
    }
}
