<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class Acl_has_Roles extends Model
{
    public $timestamps = false;
    
    public function store(Request $request, Access_List $access_list)
    {
        $role        = Role::find($request->role);
        $access_list = Access_List::find($access_list->id);
        
        $role->user()->attach($access_list);
    }
}
