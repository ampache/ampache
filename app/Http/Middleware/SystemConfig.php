<?php

namespace App\Http\Middleware;

use Closure;

use App\Services\UserPreferences;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SystemConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $id = Auth::id();
        } else {
            $id= 0;
        }
        
        return $next($request);
    }
}
