<?php

namespace App\Providers;

use App\Services\Registration;
use Illuminate\Support\ServiceProvider;

class RegistrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('Registration', function () {
            return new Registration();
        });
    }
}
