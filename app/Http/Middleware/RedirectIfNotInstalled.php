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
        $installed= $this->isInstalled();
        if (!$installed) {
            return redirect('install.language');
        } else {
            return $next($request);
        }
    }
    
    function isInstalled()
    {
        
        $envFile = base_path('.env');
        $envStr  =file_get_contents($envFile);
        $keys = explode("\n", $envStr);
        foreach ($keys as $key)
        {
            $t = explode("=", $key);
            if ($t[0] == "DB_DATABASE") {
                return trim($t[1]) == "null" ? false : true; 
            }           
        }
    }
}
