<?php

namespace App\Http\Controllers;

use App\Models\User_Preference;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AccessService;
use App\Services\PreferenceService;

class User_PreferenceController extends Controller
{
    protected $preferences;
  
    
    public function __construct()
    {
//        $this->Access = $access;
        $this->preferences  = $preferences;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User_Preference  $user_Preference
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User_Preference  $user_Preference
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $client      = \App\Models\User::find($id);
        $preferences = $this->preferences->get_all($client->id);

        return view('users.editPreferences', ['client' => $client, 'preferences' => $preferences]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User_Preference  $user_Preference
     * @return \Illuminate\Http\Response
     */
    public function update(User_Preference $user_Preference, Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User_Preference  $user_Preference
     * @return \Illuminate\Http\Response
     */
    public function destroy(User_Preference $user_Preference, Request $request)
    {
        //
    }
}
