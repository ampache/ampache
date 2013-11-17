<?php

//
// using: http://code.google.com/p/protobuf-socket-rpc/
//
abstract class PhpBuf_RPC_Service_Client {
    
    /**
     * @var PhpBuf_RPC_Balancer_Interface
     */
    protected $balancer;
    
    /**
     * @var string
     */
    protected $serviceFullQualifiedName = '';
    
    /**
     * @var array
     */
    protected $registerMethodResponderClasses = array();
    
    /**
     * @param PhpBuf_RPC_Context $context
     * @param PhpBuf_RPC_Socket_Factory $factory
     */
    public function __construct(PhpBuf_RPC_Context $context, PhpBuf_RPC_Socket_Factory $factory = null){
        if(null === $factory){
            $factory = new PhpBuf_RPC_Socket_Factory;
        }
        $this->balancer = new PhpBuf_RPC_Balancer_Random($context, $factory);
    }
    
    protected function setServiceFullQualifiedName($serviceFullQualifiedName){
        $this->serviceFullQualifiedName = $serviceFullQualifiedName;
    }
    
    protected function registerMethodResponderClass($methodName, $className){
        $this->registerMethodResponderClasses[$methodName] = $className;
    }
    
    protected function getMethodResponderClass($methodName){
        return $this->registerMethodResponderClasses[$methodName];
    }
    
    /**
     * @param PhpBuf_RPC_Message_Request $request
     * @return PhpBuf_RPC_Message_Response
     */
    public function request(PhpBuf_RPC_Message_Request $request){
        try {
            $writer = new PhpBuf_IO_Writer;
            $request->write($writer);
            
            $socket = $this->balancer->get();
            $socket->write($writer->getData(), $writer->getLenght());
            $socket->shutdownWrite();
            
            $resultData = $socket->read(4096);
            $socket->shutdownRead();
            $socket->close();
            
            $response = new PhpBuf_RPC_Message_Response;
            $response->read(new PhpBuf_IO_Reader($resultData));
            return $response;
        } catch(PhpBuf_RPC_Socket_Exception $e){
            throw new PhpBuf_RPC_Exception($e->getMessage(), PhpBuf_RPC_Message_ErrorReason::IO_ERROR);
        }
    }
    
    public function callRPC($serviceName, $methodName, PhpBuf_Message_Abstract $requestMessage, $responderClassName){
        $writer = new PhpBuf_IO_Writer;
        $requestMessage->write($writer);
        
        $request = new PhpBuf_RPC_Message_Request;
        $request->serviceName = $serviceName;
        $request->methodName = $methodName;
        $request->requestProto = $writer->getData();
        $response = $this->request($request);
        if(null !== $response->error && null !== $response->errorReason){
            throw new PhpBuf_RPC_Exception($response->error, $response->errorReason);
        }
        
        $instance = new $responderClassName;
        $instance->read(new PhpBuf_IO_Reader($response->responseProto));
        return $instance;
    }
    
    public function callMethod($methodName, PhpBuf_Message_Abstract $requestMessage){
        $responderClassName = $this->getMethodResponderClass($methodName);
        return $this->callRPC($this->serviceFullQualifiedName, $methodName, $requestMessage, $responderClassName);
    }
    
    public function __call($methodName, array $args = array()){
        if(empty($args)){
            throw new InvalidArgumentException('arguments was empty');
        }
        return $this->callMethod($methodName, $args[0]);
    }
    
}