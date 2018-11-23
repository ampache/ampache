<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Preference_has_roles extends Model
{
    public $timestamps = false;
    
    public function store(Request $request, preference $preference)
    {
        $role       = Role::find($request->role);
        $preference = Preference::find($preference->id);
        
        $role->user()->attach($preference);
    }
}
