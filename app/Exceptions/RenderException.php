<?php

namespace App\Exceptions;

use Exception;
use Swift_TransportException;

class RenderException extends Exception
{
    public function render($request, Exception $exception)
    {
        if ($exception instanceof Swift_TransportException) {
            return back()
            ->with('status', "This is a test")->withInput();
        }
        
        return parent::render($request, $exception);
    }
}
