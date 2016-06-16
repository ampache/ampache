<?php

namespace App\Providers;

use App\Services\Catalog;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
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
        app()->singleton('Catalog', function () {
            return new Catalog();
        });
    }
}
