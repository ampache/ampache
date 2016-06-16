<?php

namespace App\Services;

class Playlist
{
    /**
     * Construct an instance of Playlist service.
     *
     */
    public function __construct()
    {
        
    }
    
    public function hasItems($playlist_id)
    {
        if ($playlist_id > 0) {
            $playlist = \App\Models\Playlist::get($playlist_id);
            if ($playlist !== null) {
                return true;
            }
        }
        return false;
    }
    
    public function getTracks($playlist_id)
    {
        return array();        
    }
}