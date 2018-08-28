<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference_role extends Model
{
    public $timestamps = false;
    protected $fillable = ['id', 'preference_id', 'role'];
}
