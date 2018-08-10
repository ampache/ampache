<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\UserPreferences;
use App\Services\AccessService;

class PreferenceServiceProvider extends ServiceProvider
{
    protected $defer = true;
    
    protected $access;
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(AccessService $access)
    {
        $this->access = $access;
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\UserPreferences', function ($app) {
            return new UserPreferences($this->access);
        });
    }
    
    public function provides()
    {
        return [UserPreferences::class];
    }
}
