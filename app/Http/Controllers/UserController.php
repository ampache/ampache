<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;

use App\Models\User;

class UserController extends Controller
{
    protected $model;
    
    public function __construct(User $model)
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
        $users = $this->model->paginate(\Config::get('theme.threshold'));
        $links = $users->setPath('')->render();
        
        return view('user.index', compact('users', 'links'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(UserCreateRequest $request)
    {
        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */

    public function store(Request $request)
    {
        $user = $this->model->create($request->all());

        return redirect('user')->withOk(T_('User created.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $user = $this->model->findOrFail($id);

        return view('user.show',  compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $user = $this->model->findOrFail($id);

        return view('user.edit',  compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(UserUpdateRequest $request, $id)
    {
        $this->model->findOrFail($id)->fill($request->all())->save();
        
        return redirect('user')->withOk('User updated.');
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
