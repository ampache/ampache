<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class role_user extends Model
{
    public function store(Request $request, User $user)
    {
        $role = Role::find($request->role);
        $user = User::find($user->id);

        $role->user()->attach($user);
    }
}
