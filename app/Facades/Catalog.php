<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Catalog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Catalog';
    }
}