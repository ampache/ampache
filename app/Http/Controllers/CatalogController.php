<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Catalog as Catalogs;
use App\Classes\Catalog as cat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalogs\Local\Catalog_local;
use Modules\Catalogs\Remote\Catalog_remote;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Providers\CatalogServiceProvider;
//use App\Services\Catalog;


class CatalogController extends Controller
{
    protected $catalogs;
    
    public function __construct(User $users)
    {
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        return view('catalogs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('catalogs.create', compact('Users', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $catalog_id = cat::create($data);
        if ($catalog_id == false) {
            return response('Folder Path already used for catalog', 200);
        }
        return response('Catalog was created', 200);
        $catalogs[] = $catalog_id;
//        Catalog::catalog_worker('add_to_catalog', $catalogs, $_POST);        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function show(Catalog $catalog)
    {
        $catalogs = array();
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $modules[] = new $class_name();
        }
        
        return view('modules.catalog.index', compact('modules'));    
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $catalog_type = DB::table('catalogs')->select('catalog_type')->where('id', '=', $id)->first();
        $table = 'catalog_' . $catalog_type->catalog_type;
        $catalog = DB::table('catalogs')->join($table, 'catalogs.id', '=', $table . '.catalog_id')->where('catalogs.id', '=', $id)->first();
        
        return view('catalogs.edit', compact('catalog'));
        
    }
    
    public function action($action, $id) {
        
        switch ($action)
        {
            case "add_to_catalog":
                
                break;
            case "update_catalog":
                
                break;
            case "clean_catalog":
                break;
            case "full_service":
                
                break;
            case "gather_art":
                
                break;
            case "delete_catalog":
                $this->delete($id);
                return response('Deleted', 200);
                break;
            default:
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $attributes = $request->all();
        $catalog = Catalogs::find($attributes['catalog_id']);
        $catalog->name = $attributes['name'];
        $catalog->rename_pattern = $attributes['rename_pattern'];
        $catalog->sort_pattern = $attributes['sort_pattern'];
        $catalog->owner = $attributes['owner'];
        $catalog->save();
        return response('Saved', 200);
        
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Catalog $catalog)
    {
        
    }
    
    public function delete($catalog_id)
    {
        // Large catalog deletion can take time
        set_time_limit(0);
        
        // First remove the songs in this catalog
        $db_results = DB::table('songs')->where("catalog", "=", $catalog_id)->delete();
        // Only if the previous one works do we go on
        $this->clean_empty_albums();
        $catalog_type = DB::table('catalogs')->select('catalog_type')->where('id', '=', $catalog_id)->first();
        
        if (!$catalog_type) {
            return false;
        }
        
        $table = 'catalog_' . $catalog_type->catalog_type;
        DB::table($table)->where('catalog_id', '=', $catalog_id)->delete();
               
        // Next Remove the Catalog Entry it's self
        DB::table('catalogs')->where('id', '=', $catalog_id)->delete();

        return true;
        
    }
    public function clean_empty_albums()
    { 
            $empties = DB::table('albums')->select('id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw('`id` FROM `songs` WHERE `songs`.`album` = `albums`.`id`'));
            })
            ->get();
            
            if ($empties->count() > 0) {
                
            }
            
    }
        
}
