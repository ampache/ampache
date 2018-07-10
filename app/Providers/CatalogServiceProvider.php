<?php

namespace App\Providers;

use App\Services\CatalogService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Catalogs\Local;

class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $cat_types = CatalogService::show_catalog_types();
        view::share('cat_types', $cat_types[0]);
        view::share('sel_types', $cat_types[1]);
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('Catalog', function () {
            return new CatalogService();
        });
    }
}
