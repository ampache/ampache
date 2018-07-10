<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PDO;
use Closure;

class BeforeMiddleware
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
        //First test connection with authorized username/password
        $conn;
        $dsn = 'mysql:dbname=' . env('DB_DATABASE') . ";host=" . env('DB_HOST') ;
        try {
            $conn = new \PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'));
        } catch (\PDOException $e) {
            $message = $this->getPDOMessage($e->getCode());
            abort(503, $message);
        }
        $sql  = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . env('DB_CONNECTION') . "';";
        $rows = $conn->query($sql);
        if (count($rows) == 0) {
            abort(503, 'Database: ' . env('DB_USERNAME') . ' Doesn\'t exist.');
        }
        if (!Schema::hasTable('users')) {
            abort(503, 'Database tables not found. Please run "php artisan migrate"');
        }
            
        return $next($request);
    }
    
    private function getPDOMessage($errorNo)
    {
        switch ($errorNo) {
            case 1007:
                $message = "Can't create database '" . $this->database . "'; database exists and overwrite is not enabled.";
            case 1045:
                $message = "Administrative username or password incorrect.";
                break;
            case 1049:
                $message = "Unknown Database";
                break;
            case 2002:
                $message = "Hostname/IP incorrect.";
                break;
            case 1130:
                $message = "Host '" . gethostname() . "' is not allowed to connect to this MySQL server.";
            default:
                $message = "Unknown PDO error.";
        }
        
        return $message;
    }
}
