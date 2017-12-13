<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CatalogCreateRequest;
use App\Http\Requests\CatalogUpdateRequest;
use App\Support\UI;
use App\Models\Catalog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    
    protected $catalogs;
    
    public function __construct(Catalog $catalogs)
    {
        $this->catalogs = $catalogs;
        $this->middleware('web');
    }
    
     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        UI::flip_class(array('odd', 'even'));
        $catalogs = $this->model->paginate(config('theme.threshold'));
        $links    = $catalogs->setPath('')->render();
        
        return view('catalog.index', compact('catalogs', 'links'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $ampacheTables = DB::select('SHOW TABLES');
        foreach ($ampacheTables as $table) {
            if (strpos($table->Tables_in_ampache, 'catalog') > -1) {
                $catalog[] = $table->Tables_in_ampache;
            }
        }
//        $catalog_types = $this->get_catalog_types();
        return view('catalog.create', compact('catalogs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */

    public function store(CatalogCreateRequest $request)
    {
        $catalog = $this->model->create($request->all());

        return redirect('catalog')->withOk(T_('Catalog created.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $catalog = $this->model->findOrFail($id);

        return view('catalog.show', compact('catalog'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $catalog = $this->model->findOrFail($id);

        return view('catalog.edit', compact('catalog'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(CatalogUpdateRequest $request, $id)
    {
        $this->model->findOrFail($id)->fill($request->all())->save();
        
        return redirect('catalog')->withOk('Catalog updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $this->model->findOrFail($id)->delete();

        return redirect()->back();
    }
    
    public function show_catalog_types()
    {
        $catalogs = $this->get_catalog_types();
        return view('includes.catalog_types', compact('catalogs'));
    }
    
    /**
     * get_catalog_types
     * This returns the catalog types that are available
     * @return string[]
     */
    public static function get_catalog_types()
    {
        /* First open the dir */
        $basedir = base_path( 'modules/catalog');
        $handle  = opendir($basedir);
        
        if (!is_resource($handle)) {
            Log::debug('catalog', 'Error: Unable to read catalog types directory');
            return array();
        }
        
        $results = array();
        
        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            /* Make sure it is a dir */
            if (! is_dir($basedir . '/' . $file)) {
                Log::debug('catalog', $file . ' is not a directory.');
                continue;
            }
            
            // Make sure the plugin base file exists inside the plugin directory
            if (! file_exists($basedir . '/' . $file . '/' . $file . '.catalog.php')) {
                Log::debug('catalog', 'Missing class for ' . $file);
                continue;
            }
            
            $results[] = $file;
        } // end while
        
        return $results;
    } // get_catalog_types
    
    
}
