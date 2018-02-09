<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SidebarController extends Controller
{
    public function loadTab(Request $request, $tab)
    {
        switch ($tab)
        {
            case 'home':
                return view('partials.sidebar.home');
            case 'preferences':
                return view('partials.sidebar.preferences');
            case 'localplay':
                return view('partials.sidebar.localplay');
            case 'modules':
                return view('partials.sidebar.modules');
            case 'admin':
                return view('partials.sidebar.admin');
            default:
                Auth::logout();
                return view('/');
        }
    }
}
