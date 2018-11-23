<?php

namespace App\Http\Controllers;

use App\Classes\Catalog;
use App\Models\Catalog_local;
use App\Models\User;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Remote\Catalog_remote;
use Illuminate\Support\Facades\View;

//use App\Services\Catalog;


class CatalogController extends Controller
{
    protected $preferences;
    
    public function __construct(Preference $preferences)
    {
        $this->preferences = $preferences;
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
        $cat_types = Catalog::show_catalog_types();
        view::share('cat_types', $cat_types[0]);
        view::share('sel_types', $cat_types[1]);
        
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
        $data       = $request->all();
        $type       = $data['catalog_type'];
        $path       = $data['path'];

        // Make sure this path isn't already in use by an existing catalog
        if ($this->isDuplicatePath($type, $path)) {
            return back()->with('status', sprintf(__('Error: Catalog with path %s already exists'), $path));
        }
        
        // Make sure the path is readable/exists
        if (!is_readable($path) || !is_dir($path)) {
            //            //debug_event('catalog', 'Cannot add catalog at unopenable path ' . $path, 1);
            Log::error(sprintf('Error: %s is not readable or does not exist', e($path)));

            return back()->with('status', sprintf('Error: %s is not readable or does not exist', e($data['path'])));
        }
        if (config('catalog.allow_embedded_catalogs') == false) {
            // Make sure that there isn't a catalog with a directory above this one
            if ($this->isEmbeddedPath($type, $path)) {
                Log::error('Error: Defined Path is inside an existing catalog');

                return back()->with('status', 'Defined Path is inside an existing catalog!');
            }
        }
        
        $catalog_id = Catalog::create($data);
        if ($catalog_id) {
            $catalogs[] = $catalog_id;
            
 //           Catalog::catalog_worker('add_to_catalog', $catalogs, $_POST);
        } else {
            Log::error('Error: Defined Path is inside an existing catalog');

            return back()->with('status', 'Catalog creation failed!');
        }
        

        $nextPath= url('catalogs/index');
        $text    = "Catalog creation started...";
        $cancel  = false;
        $temp    = array($data, $nextPath . $cancel, $text);

        return view('catalogs.confirm', ['text' => $text, 'cancel' => $cancel, 'nextPath' => $nextPath]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function show(Catalog $catalog)
    {
        $catalogs      = array();
        $catalog_types = $this->get_module_types('catalog');
        foreach ($catalog_types as $type) {
            $class_name = '\\Modules\\Catalog\\' . ucfirst($type) . '\\' . 'Catalog_' . strtolower($type) ;
            $modules[]  = new $class_name();
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
        $table        = 'catalog_' . $catalog_type->catalog_type;
        $catalog      = DB::table('catalogs')->join($table, 'catalogs.id', '=', $table . '.catalog_id')->where('catalogs.id', '=', $id)->first();
        
        return view('catalogs.edit', compact('catalog'));
    }
    
    public function action(Request $request, $action, $id)
    {
        $nextPath= url('catalogs/index');
        switch ($action) {
            case 'add_to_all_catalogs':
                catalog_worker('add_to_all_catalogs');

                return view('catalogs.confirm', ['text' => __('Catalog Update started...'), 'cancel' => false, 'nextPath' => $nextPath]);
                break;
            case 'add_to_catalog':
                if (config('program.demo_mode') == true) {
                    break;
                }
                
                catalog_worker('add_to_catalog', $catalogs);

                return view('catalogs.confirm', ['text' => __('Catalog Update started...'), 'cancel' => false, 'nextPath' => $nextPath]);
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
    
    public function catalog_worker($action, $catalogs = null, $options = null)
    {
        $ajax_load = $this->preferences->select('value')->where('name', '=', 'ajax_load')->get();
        if ($ajax_load) {
            $sse_url = url("/sse") . "/" . $action . "/" . $action . "/" . urlencode(json_encode($catalogs));
            if ($options) {
                $sse_url .= "&options=" . urlencode(json_encode($_POST));
            }
            $this->sse_worker($sse_url);
        } else {
            Catalog::process_action($action, $catalogs, $options);
        }
    }
    
    public function sse_worker($url)
    {
        echo '<script type="text/javascript">';
        echo "sse_worker('$url');";
        echo "</script>\n";
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
        $attributes              = $request->all();
        $catalog                 = DB::table('catalogs')->find($attributes['catalog_id']);
        $catalog->name           = $attributes['name'];
        $catalog->rename_pattern = $attributes['rename_pattern'];
        $catalog->sort_pattern   = $attributes['sort_pattern'];
        $catalog->owner          = $attributes['owner'];
        $catalog->save();

        return response('Saved', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    
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
                $query->select(DB::raw('`id` FROM `songs` WHERE `songs`.`album_id` = `albums`.`id`'));
            })
            ->get();
            
        if ($empties->count() > 0) {
        }
    }
    
    public function isDuplicatePath($type, $path)
    {
        $exists = DB::table('catalog_' . $type)->where('path', $path)->exists();
        Log::error('Cannot add catalog with duplicate path: ' . $path);

        return $exists;
    }
    
    public function isEmbeddedPath($type, $path)
    {
        $Catalog_type = 'Catalog_' . $type;
        $isEmbedded   = $Catalog_type::get_from_path($path) ;

        return $isEmbedded;
    }
}
