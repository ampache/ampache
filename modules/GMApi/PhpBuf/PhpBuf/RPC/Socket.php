<?php

class PhpBuf_RPC_Socket implements PhpBuf_RPC_Socket_Interface {
    
    const SOCKET_READ_END = 0;
    
    const SOCKET_WRITE_END = 1;
    
    const SOCKET_READ_WRITE_END = 2;
    
    const TIMEOUT_USEC = 200000;
    
    const RETRY_THESHOLD = 1000;
    
    // SOCKET_EWOULDBLOCK || SOCKET_EAGAIN ::=> "Resource temporarily unavailable"
    protected static $errorAgain = array(
        11,
        35
    );
    
    protected $socket;
    
    protected $closed = false;
    
    public function __construct($host, $port){
        $ipAddr = self::DEFAULT_IPADDR;
        if(false === ip2long($host)){
            $ipAddr = gethostbyname($host);
        } else {
            $ipAddr = $host;
        }
        
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(false === $socket){
            throw new PhpBuf_RPC_Socket_Exception('socket creation fail:' . socket_strerror(socket_last_error()));
        }
        
        $connected = socket_connect($socket, $ipAddr, $port);
        if(false === $connected){
            throw new PhpBuf_RPC_Socket_Exception('socket connection fail:' . socket_strerror(socket_last_error()));
        }
        
        socket_set_nonblock($socket);
        // socket_set_timeout($socket, 5);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        if(defined('TCP_NODELAY')){
            socket_set_option($socket, SOL_SOCKET, TCP_NODELAY, 1);
        }
        if(!defined('SOCKET_EWOULDBLOCK')){
            define('SOCKET_EWOULDBLOCK', 35);
        }
        if(!defined('SOCKET_EAGAIN')){
            define('SOCKET_EAGAIN', 35);
        }
        $this->socket = $socket;
    }
    public function __destruct(){
        if(!$this->closed){
            $this->close();
        }
    }
    
    public function read($length = 1024){
        $retry = 0;
        $data = '';
        while(true){
            $read = array($this->socket);
            $write = array();
            $except = array();
            
            $r = socket_select($read, $write, $except, 0, self::TIMEOUT_USEC);
            if(false === $r){
                throw new PhpBuf_RPC_Socket_Exception('socket select fail: ' . socket_strerror(socket_last_error()));
            }
            if(0 === $r){
                continue;
            }
            
            $result = @socket_read($this->socket, $length, PHP_BINARY_READ);
            if(false === $result){
                if($retry < self::RETRY_THESHOLD){
                    $error = socket_last_error();
                    if(in_array($error, self::$errorAgain)){
                        usleep(self::TIMEOUT_USEC);
                        $retry++;
                        continue;
                    }
                }
                throw new PhpBuf_RPC_Socket_Exception('read error:' . socket_strerror(socket_last_error()));
            }
            if(empty($result)){
                break;
            }
            $data .= $result;
        }
        return $data;
    }
    
    public function write($data, $length = null){
        $msgLength = $length;
        if(null === $length){
            $msgLength = strlen($data);
        }
        
        $retry = 0;
        $offset = 0;
        while($offset < $msgLength){
            $size = @socket_write($this->socket, substr($data, $offset), $msgLength - $offset);
            if(false === $size){
                if($retry < self::RETRY_THESHOLD){
                    $error = socket_last_error();
                    if(in_array($error, self::$errorAgain)){
                        usleep(self::TIMEOUT_USEC);
                        $retry++;
                        continue;
                    }
                }
                throw new PhpBuf_RPC_Socket_Exception('write error: ' . socket_strerror(socket_last_error()));
            }
            $offset += $size;
        }
        return $offset;
    }
    
    public function shutdownRead(){
        return @socket_shutdown($this->socket, self::SOCKET_READ_END);
    }
    
    public function shutdownWrite(){
        return @socket_shutdown($this->socket, self::SOCKET_WRITE_END);
    }
    
    public function shutdown(){
        return @socket_shutdown($this->socket, self::SOCKET_READ_WRITE_END);
    }
    
    public function close(){
        $this->closed = true;
        @socket_close($this->socket);
    }
}
