<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Classes\Catalog;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       if (Auth::check() OR !isset($_COOKIE['sidebar_tab'])) {
               setcookie('sidebar_tab', 'home', time() + (30 * 24 * 60 * 60), '/');
            }
        else {
            setcookie('sidebar_tab', 'home', time() + (30 * 24 * 60 * 60), '/');        
        }
        
        if (!isset($_COOKIE['sidebar_state'])) {
            setcookie('sidebar_state', 'expanded', time() + (30 * 24 * 60 * 60), '/');
        }
        $sb_homeItems['sb_music']  = isset($_COOKIE['sb_music']) ? : 'collapsed';
        $sb_homeItems['sb_video']  = isset($_COOKIE['sb_video']) ? : 'collapsed';
        $sb_homeItems['sb_information']  = isset($_COOKIE['sb_information']) ? : 'collapsed';
        $sb_homeItems['sb_random']  = isset($_COOKIE['sb_random']) ? : 'collapsed';
        view::share('sb_homeItems', $sb_homeItems);
        
        $Catalog_ids = Catalog::get_catalogs();
        $libitem = array();
        foreach ($Catalog_ids as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->format();
            $Catalogs[] = $catalog;
            view::share('Catalogs', $Catalogs);
        }
            $Users = user::all();
            view::share('Users', $Users);
            $cat_types = Catalog::show_catalog_types();
            $sel_types = Catalog::get_catalog_types();
            array_unshift($sel_types, "[select]");
            view::share('cat_types', $cat_types);
            view::share('sel_types', $sel_types);
            
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
