<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CatalogCreateRequest;
use App\Http\Requests\CatalogUpdateRequest;

use App\Models\Catalog;

class CatalogController extends Controller
{
    protected $model;
    
    public function __construct(Catalog $model)
    {
        $this->model = $model;
        $this->middleware('web');
    }
    
     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $catalogs = $this->model->paginate(\Config::get('theme.threshold'));
        $links = $catalogs->setPath('')->render();
        
        return view('catalog.index', compact('catalogs', 'links'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('catalog.create');
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

        return view('catalog.show',  compact('catalog'));
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

        return view('catalog.edit',  compact('catalog'));
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
    
}
