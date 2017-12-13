<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Song_data;

class Song extends Model
{
    protected $fillable = ['file', 'catalog', 'album', 'artist', 
            'title', 'bitrate', 'rate', 'mode', 'size', 'time', 'track',
            'addition_time', 'year', 'mbid', 'user_upload', 'license',
            'composer', 'channels'
    ];
    
    public function fill(array $attributes){
        
    }
    
    public function create($attributes)
    {
        parent::create($attributes);
        $song_data = new Song_data();
        $song_data->create($attributes);
        
    }
}
