<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Classes\Catalog;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\AmpError;

class AppServiceProvider extends ServiceProvider
{
    
   /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        
        if (!isset($_COOKIE['sidebar_tab'])) {
            setcookie('sidebar_tab', 'home', time() + (30 * 24 * 60 * 60), '/');
        }

        if (!isset($_COOKIE['sidebar_state'])) {
            setcookie('sidebar_state', 'expanded', time() + (30 * 24 * 60 * 60), '/');
        }
        $sb_homeItems                    = array();
        $sb_homeItems['sb_music']        = isset($_COOKIE['sb_music']) ? : 'collapsed';
        $sb_homeItems['sb_video']        = isset($_COOKIE['sb_video']) ? : 'collapsed';
        $sb_homeItems['sb_information']  = isset($_COOKIE['sb_information']) ? : 'collapsed';
        $sb_homeItems['sb_random']       = isset($_COOKIE['sb_random']) ? : 'collapsed';
        view::share('sb_homeItems', $sb_homeItems);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->singleton('Registration', function () {
            return new AmpError();
        });
    }
}
