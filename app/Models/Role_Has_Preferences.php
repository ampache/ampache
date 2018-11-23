<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role_has_preferences extends Model
{
    public $timestamps  = false;
    protected $fillable = ['id', 'preference_id', 'role'];
}
