<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Update_Info extends Model
{
    protected $table = 'update_info';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];
    

}
