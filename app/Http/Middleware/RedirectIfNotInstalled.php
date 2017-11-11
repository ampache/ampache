<?php

namespace App\Http\Middleware;

use Closure;

class RedirectIfNotInstalled
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
        $installed = config('app.installed');
        if (!config('app.installed')) {
            return redirect('install.language');
        } else {
            return $next($request);
        }
    }
}
