<?php

class PhpBuf_RPC_Message_ErrorReason extends PhpBuf_Message_Abstract {
    //
    // {{{ Server-side errors
    //
    
    // Server received bad request data
    const BAD_REQUEST_DATA = 0;
    // Server received bad request proto
    const BAD_REQUEST_PROTO = 1;
    // Service not found on server
    const SERVICE_NOT_FOUND = 2;
    // Method not found on server
    const METHOD_NOT_FOUND = 3;
    // Rpc threw exception on server
    const RPC_ERROR = 4;
    // Rpc failed on server
    const RPC_FAILED = 5;
    
    //
    // }}} Server-side errors
    //
    
    //
    // {{{ Client-side errors (these are returned by the client-side code)
    //
    
    // Rpc was called with invalid request proto
    const INVALID_REQUEST_PROTO = 6;
    // Server returned a bad response proto
    const BAD_RESPONSE_PROTO = 7;
    // Could not find supplied host
    const UNKNOWN_HOST = 8;
    // I/O error while communicating with server
    const IO_ERROR = 9;
    
    //
    // }}} Client-side errors
    //
    
    public static function name(){
        return __CLASS__;
    }
    
    public static function values(){
        return array(
            self::BAD_REQUEST_DATA,
            self::BAD_REQUEST_PROTO,
            self::SERVICE_NOT_FOUND,
            self::METHOD_NOT_FOUND,
            self::RPC_ERROR,
            self::RPC_FAILED,
            self::INVALID_REQUEST_PROTO,
            self::BAD_RESPONSE_PROTO,
            self::UNKNOWN_HOST,
            self::IO_ERROR
        );
    }
}