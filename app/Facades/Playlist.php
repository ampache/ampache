<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Playlist extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Playlist';
    }
}