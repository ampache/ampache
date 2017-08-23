<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function setLanguage()
    {
        $languages = array_keys(config('languages'));
        
        return view('install.language', compact('languages'));
    
    }
}
