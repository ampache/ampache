<?php

namespace App\Providers;

use App\Services\Playlist;
use Illuminate\Support\ServiceProvider;

class PlaylistServiceProvider extends ServiceProvider
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
        app()->singleton('Playlist', function () {
            return new Playlist();
        });
    }
}
