<?php

class PhpBuf_RPC_Message_Request extends PhpBuf_Message_Abstract {
    
    protected $requestMessage;
    
    public function __construct(){
        $this->setField('serviceName', PhpBuf_Type::STRING, PhpBuf_Rule::REQUIRED, 1);
        $this->setField('methodName', PhpBuf_Type::STRING, PhpBuf_Rule::REQUIRED, 2);
        $this->setField('requestProto', PhpBuf_Type::BYTES, PhpBuf_Rule::REQUIRED, 3);
    }
    
    public function setRequestMessage(PhpBuf_Message_Abstract $message){
        $this->requestMessage = $message;
    }
    
    public function getRequestMessage(){
        return $this->requestMessage;
    }
    
    public static function name(){
        return __CLASS__;
    }
}