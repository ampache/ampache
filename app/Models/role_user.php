<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class role_user extends Model
{
    public function store(Request $request, User $user)
    {
        $role = Role::find($request->role);
        $user = User::find($user->id);

        $role->user()->attach($user);
    }
}
