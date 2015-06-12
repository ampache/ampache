<?php
// Throw exceptions on all PHP errors/warnings/notices.
// We'd like to do this in all situations (and not just when running tests), but
// this is a global setting and other code might not be ready for it.
/** @internal */
function error_to_exception($errno, $errstr, $errfile, $errline, $context)
{
    // If the error is being suppressed with '@', don't throw an exception.
    if (error_reporting() === 0) return;

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('error_to_exception');
