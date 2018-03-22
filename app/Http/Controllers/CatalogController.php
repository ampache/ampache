<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Classes\Catalog as cats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Local\Catalog_local;
use Modules\Catalog\Remote\Catalog_remote;

class CatalogController extends Controller
{
    protected $catalogs;
    
    public function __construct(Catalog $catalogs)
    {
        $this->catalogs = $catalogs;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $catalogs = $this->catalogs->paginate(15);
        return view('catalogs.index', ['Catalogs' => $catalogs]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $catalogTypes = cats::show_catalog_types();
            return view('catalogs.create', compact('catalogTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
    public function edit(Catalog $catalog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Catalog $catalog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Catalog $catalog)
    {
        //
    }
        
}
