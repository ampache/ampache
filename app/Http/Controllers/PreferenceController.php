<?php

namespace App\Http\Controllers;

use App\Models\Preference;
use App\Models\Preference_role;
use App\Models\Role;
use Illuminate\Http\Request;
use Xinax\LaravelGettext\Facades\LaravelGettext;
use App\Models\User;

class PreferenceController extends Controller
{
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
        $preferences = Preference::where('category', '=', 'interface')
        ->orderBy('subcategory', 'asc')->simplePaginate(15);
        $roles = Role::all();            
        return view('preferences.edit', ['preferences' => $preferences, 'roles' => $roles]);
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
        $data = $request->all();
        $array1 = array('_token' => 1, '_method' => 2, 'basic' => 3);
        $name = key(array_diff_key($data, $array1));
        $result = Preference_role::where('preference_id', '=', $id)->delete();
        foreach ($data['basic'] as $role_id)
        {
            $role = Preference_role::insert( ['role_id' => $role_id,'preference_id' => (int)$id], ['role_id' => $role_id,'preference_id' => (int)$id]);
        }
        $result = Preference::find($id);
        if ($result->value != $data[$name])
        {
            $result->value = $name[0];
            $result->save();
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
