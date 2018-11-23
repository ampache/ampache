<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Schema;
use Closure;
use PDO;

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
        if (!env('AMPACHE_INSTALLED')) {
            abort(503);
        }
          
        if (!env('DB_DATABASE')) {
            abort(503, 'Database environment not set in .ENV file.');
        }
        if (!env('DB_USERNAME', false)) {
            abort(503, 'DB_USERNAME not set in .ENV file.');
        }
        if (!env('DB_PASSWORD', false)) {
            abort(503, 'DB_PASSWORD not set in .ENV file.');
        }
        if (!env('DB_HOST', false)) {
            abort(503, 'DB_HOST not set in .ENV file.');
        }
        if (!env('DB_PORT', false)) {
            abort(503, 'DB_PORT not set in .ENV file.');
        }
        if (!env('DB_CONNECTION', false)) {
            abort(503, 'DB_CONNECTION not set in .ENV file.');
        }

        $conn;
        $dsn = 'mysql:dbname=' . env('DB_DATABASE') . ";host=" . env('DB_HOST') ;
        try {
            $conn = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'));
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            abort(503, $message);
        }
          
        return $next($request);
    }
}
