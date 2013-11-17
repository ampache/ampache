<?php

class PhpBuf_RPC_Message_Response extends PhpBuf_Message_Abstract {
    public function __construct(){
        $this->setField('responseProto', PhpBuf_Type::BYTES, PhpBuf_Rule::OPTIONAL, 1);
        $this->setField('error', PhpBuf_Type::STRING, PhpBuf_Rule::OPTIONAL, 2);
        $this->setField('callback', PhpBuf_Type::BOOL, PhpBuf_Rule::OPTIONAL, 3);
        $this->setField('errorReason', PhpBuf_Type::ENUM, PhpBuf_Rule::OPTIONAL, 4, PhpBuf_RPC_Message_ErrorReason::values());
    }
    
    public static function name(){
        return __CLASS__;
    }
}