<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;

class Catalog extends Model
{
    public $timestamps = false;    //
    protected $dateFormat = 'U';
    public function getLastUpdateAttribute($value)
    {
        $format = "m/d/Y H:i:s";
        return DateTime::createFromFormat($format, $value);
        
    }
}
