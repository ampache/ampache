<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class user_has_roles extends Model
{
    public $timestamps = false;
    
    public function store(Request $request, User $user)
    {
        $role = Role::find($request->role);
        $user = User::find($user->id);

        $role->user()->attach($user);
    }
}
