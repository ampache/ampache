<?php

namespace App\Http\Middleware;

use Closure;

use App\Facades\AmpConfig;
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
        if (AmpConfig::get('lang', 'empty') == 'empty') {
            $preferences = db::table('preferences')->get();
            foreach ($preferences as $preference) {
                AmpConfig::set($preference->name, $preference->value, false);
            }
        }

        return $next($request);
    }
}
