<?php

class PhpBuf_RPC_Balancer_Random implements PhpBuf_RPC_Balancer_Interface {
    
    /**
     * @var PhpBuf_RPC_Context
     */
    protected $context;
    
    /**
     * @var PhpBuf_RPC_Socket_Factory
     */
    protected $factory;
    
    /**
     * @param PhpBuf_RPC_Context $context
     */
    public function __construct(PhpBuf_RPC_Context $context, PhpBuf_RPC_Socket_Factory $factory){
        $this->context = $context;
        $this->factory = $factory;
    }
    
    /**
     * @return PhpBuf_RPC_Socket_Interface
     */
    public function get(){
        $copy = (array) $this->context->getServers();
        shuffle($copy);
        $count = count($copy);
        
        $lastException = null;
        for($i = 0; $i < $count; ++$i){
            $server = $copy[$i];
            try {
                return $this->factory->create($server['host'], $server['port']);
            } catch(PhpBuf_RPC_Socket_Exception $e){
                $lastException = $e;
                //
                // next server
                // TODO: failover
                //
            }
        }
        if(null != $lastException){
            throw new PhpBuf_RPC_Socket_Exception($lastException->getMessage(), $lastException->getCode());
        }
    }
}
