<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AccessService;

class AccessServiceProvider extends ServiceProvider
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
        app()->singleton('Registration', function () {
            return new AccessService();
        });
    }
    
    public function provides()
    {
        return [AccessService::class];
    }
}
