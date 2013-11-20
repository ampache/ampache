<?php

/**
 * @author nowelium
 */
class PhpBuf_RPC_Context {
    
    protected $servers = array();
    
    /**
     * @param string $host
     * @param integer $port
     */
    public function addServer($host, $port){
        $this->servers[] = array(
            'host' => $host,
            'port' => $port
        );
    }
    
    /**
     * @return array
     */
    public function getServers(){
        return $this->servers;
    }
}