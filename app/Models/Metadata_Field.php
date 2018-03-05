<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Metadata_Field extends Model
{
    protected $table = 'metadata_field';
    public $timestamps = false;
    public function gc()
    {

        DB::table('metadata_field')
        ->leftJoin('metadata', 'metadata.field', '=', 'metadata_field.id')
        ->whereNull('metadata.id')
        ->delete();
        
    }
}
