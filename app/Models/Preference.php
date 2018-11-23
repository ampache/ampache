<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Preference extends Model
{
    use HasRoles;
    
    protected $table   = 'preferences';
    public $timestamps = false;
    
    public function getValueAttribute($value)
    {
        if ($value === 'true') {
            return true;
        }
        
        if ($value === 'false') {
            return 'false';
        }

        return $value;
    }
}
