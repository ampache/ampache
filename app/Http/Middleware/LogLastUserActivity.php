<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class LogLastUserActivity
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
        $url= $request->getBasePath();
        if (Auth::check()) {
            $expiresAt = Carbon::now()->addMinutes(config('session.lifetime'));
            Cache::put('user-is-online-' . Auth::user()->id, true, $expiresAt);
            Cache::forget('last-activity-' . Auth::user()->id);
            $activityTime = Carbon::now()->format('Y-m-d H:i:s');
            Cache::forever('last-activity-' . Auth::user()->id, $activityTime);
        }
        
        
        if (empty(Cookie::get('sidebar_tab'))) {
            setcookie("sidebar_tab", 'home');
            $_COOKIE['sidebar_tab'] = 'home';
        }
        if (empty(Cookie::get('sidebar_state'))) {
            setcookie("sidebar_state", 'expanded');
            $_COOKIE['sidebar_state'] = 'expanded';
        }

        return $next($request);
    }
}
