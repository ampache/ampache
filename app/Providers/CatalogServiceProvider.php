<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Classes\Catalog;
use Spatie\Permission\Models\Role;
use Exception;

class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            $result = DB::table('INFORMATION_SCHEMA.SCHEMATA')->select('SCHEMA_NAME')->where('SCHEMA_NAME', '=', env('DB_DATABASE'))->count();
            if ($result > 0) {
                $Catalog_ids = Catalog::get_catalogs();
                $libitem     = array();
                foreach ($Catalog_ids as $catalog_id) {
                    $catalog = Catalog::create_from_id($catalog_id);
                    $catalog->format();
                    $Catalogs[] = $catalog;
                    view::share('Catalogs', $Catalogs);
                }
                $Users = User::all();
                view::share('Users', $Users);
                $roles = Role::get(); //Get all roles
                view::share('roles', $roles);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
