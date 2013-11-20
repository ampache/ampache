<?php

class PhpBuf_RPC_SocketFactory {
    private function __construct(){
        // nop
    }
    
    /**
     * @param string $host
     * @param int $port
     * @return PhpBuf_RPC_Socket_Interface
     */
    public static function create($host, $port){
        if(extension_loaded('sockets')){
            return new PhpBuf_RPC_Socket($host, $port);
        }
        return new PhpBuf_RPC_SocketStream($host, $port);
    }
}
