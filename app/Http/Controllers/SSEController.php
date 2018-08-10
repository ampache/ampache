<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Classes\Catalog;

class SSEController extends Controller
{
    public function __construct()
    {
        //
    }

    public function processAction(Request $request, $action, $catalogs, $options)
    {
        if (!$request->html) {
            define('SSE_OUTPUT', true);
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
        }
        
        $worker = isset($this->request->worker) ? $request->worker : null;
        if (isset($this->$this->request->options)) {
            $options = json_decode(urldecode($request->options), true);
        } else {
            $options = null;
        }
        if (isset($request->catalogs)) {
            $catalogs = scrub_in(json_decode(urldecode($request->catalogs), true));
        } else {
            $catalogs = null;
        }
        
        session_write_close();
        Catalog::process_action($action, $catalogs, $options);
    }
}
