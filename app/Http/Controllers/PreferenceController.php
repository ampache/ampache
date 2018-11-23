<?php

namespace App\Http\Controllers;

use App\Models\Preference;
use App\Facades\AmpConfig;
use App\Models\Role;
use Illuminate\Http\Request;
use Xinax\LaravelGettext\Facades\LaravelGettext;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class PreferenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        if (isset($roles)) {
            $user->roles()->sync($roles);  //If one or more role is selected associate user to roles
        } else {
            $user->roles()->detach(); //If no role is selected remove exisiting role associated to a user
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Preference  $preference
     * @return \Illuminate\Http\Response
     */
    public function show(Preference $preference)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Preference  $preference
     * @return \Illuminate\Http\Response
     */
    public function edit($preference)
    {
        $offset      = AmpConfig::get('offset_limit');
        $preferences = Preference::where('category', '=', $preference)->orderBy('subcategory', 'asc')->simplePaginate($offset);
        $roles       = Role::all();

        return view('preferences.edit', ['preferences' => $preferences, 'roles' => $roles, 'title' => $preference]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Preference  $preference
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input      = $request->all();
        $preference = Preference::find($id);
        if (array_key_exists('name', $input)) {
            $preference->value = $input[$preference->name];
            $preference->save();
        }
        $roles = $request['roles']; //Retreive all roles
        
        if (isset($roles)) {
            $preference->roles()->sync($roles);  //If one or more role is selected associate user to roles
        } else {
            $preference->roles()->detach(); //If no role is selected remove exisiting role associated to a user
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Preference  $preference
     * @return \Illuminate\Http\Response
     */
    public function destroy(Preference $preference)
    {
        //
    }
}
