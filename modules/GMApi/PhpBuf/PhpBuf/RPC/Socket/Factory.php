<?php

class PhpBuf_RPC_Socket_Factory {
    
    /**
     * @var string
     */
    protected $socketClassName;
    
    public function __construct(){
        if(extension_loaded('sockets')){
            $this->socketClassName = 'PhpBuf_RPC_Socket';
        } else {
            $this->socketClassName = 'PhpBuf_RPC_SocketStream';
        }
    }
    
    /**
     * @param string $host
     * @param integer $port
     * @return PhpBuf_RPC_Socket_Interface
     */
    public function create($host, $port){
        $className = $this->socketClassName;
        return new $className($host, $port);
    }
}
