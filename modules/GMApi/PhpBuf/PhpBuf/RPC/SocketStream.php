<?php

class PhpBuf_RPC_SocketStream implements PhpBuf_RPC_Socket_Interface {
    
    protected $socket;
    
    protected $closed = false;
    
    public function __construct($host, $port){
        $socket = stream_socket_client('tcp://' . $host . ':' . $port, $errorCode, $errorMsg, 30, STREAM_CLIENT_CONNECT);
        if(false === $socket){
            throw new PhpBuf_RPC_Socket_Exception('socket creation fail:' . $errorMsg, $errorCode);
        }
        
        stream_set_blocking($socket, 0);
        $this->socket = $socket;
    }
    
    public function __destruct(){
        if(!$this->closed){
            $this->close();
        }
    }
    
    public function read($length = 1024){
        $data = '';
        
        while(true){
            $read = array($this->socket);
            $write = array();
            $except = array();
            
            $r = stream_select($read, $write, $except, 0, self::TIMEOUT_USEC);
            if(false === $r){
                throw new PhpBuf_RPC_Socket_Exception('socket select fail');
            }
            if(0 === $r){
                continue;
            }
            
            $result = @fread($this->socket, $length);
            if(false === $result){
                throw new PhpBuf_RPC_Socket_Exception('socket read error');
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
        $offset = 0;
        while($offset < $msgLength){
            $size = @fwrite($this->socket, substr($data, $offset), $msgLength - $offset);
            if(false === $size){
                throw new PhpBuf_RPC_Socket_Exception('socket write error');
            }
            $offset += $size;
        }
        return $offset;
    }
    
    public function shutdownRead(){
        stream_socket_shutdown($this->socket, STREAM_SHUT_RD);
    }
    
    public function shutdownWrite(){
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
    }
    
    public function shutdown(){
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }
    
    public function close(){
        $this->closed = true;
        @fclose($this->socket);
    }
}
