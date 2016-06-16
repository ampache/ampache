<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Registration extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Registration';
    }
}