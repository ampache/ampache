<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $table   = 'preferences';
    public $timestamps = false;
    protected $fillable = ['preference_id', 'role_id'];
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_preferences');
    }
    
}
