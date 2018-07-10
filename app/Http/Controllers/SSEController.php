<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Classes\Catalog;
use App\Http\Controllers\Controller;
use Sse\SSE;
use Sse\Laravel\Facade;

class SSEController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
    }
    
    public function processAction($action, $catalogs, $options)
    {
        if (!$_REQUEST['html']) {
            define('SSE_OUTPUT', true);
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
        }
        
        $worker = isset($_REQUEST['worker']) ? $_REQUEST['worker'] : null;
        if (isset($_REQUEST['options'])) {
            $options = json_decode(urldecode($_REQUEST['options']), true);
        } else {
            $options = null;
        }
        if (isset($_REQUEST['catalogs'])) {
            $catalogs = scrub_in(json_decode(urldecode($_REQUEST['catalogs']), true));
        } else {
            $catalogs = null;
        }
        
        session_write_close();
        Catalog::process_action($action, $catalogs, $options);
    }
}
