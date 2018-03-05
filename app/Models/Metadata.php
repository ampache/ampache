<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Metadata extends Model
{
    protected $table = 'metadata';
    public $timestamps = false;
    
    public function gc()
    {
        DB::table('metadata')
        ->leftJoin('songs', 'songs.id', '=', 'metadata.object_id')
        ->whereNull('song.id')
        ->delete();
    }
    
}
