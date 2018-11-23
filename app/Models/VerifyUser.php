<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifyUser extends Model
{
    protected $guarded = [];
    
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'id');
    }
}
