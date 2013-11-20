<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Field_Message extends PhpBuf_Field_Abstract {
    protected static $reflectObjectCache = array();
    protected static $reflectClass = array();
    
    protected $wireType = PhpBuf_WireType::WIRETYPE_LENGTH_DELIMITED;
    
    protected function readImpl(PhpBuf_IO_Reader_Interface $reader, $repeatable = false) {
        $bytes = $this->readWireTypeData($reader);
        $refClass = self::getReflectClass($this->extra);
        $message = $refClass->newInstance();
        $message->read(new PhpBuf_IO_Reader($bytes));
        return $message;
    }
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        $newWriter = new PhpBuf_IO_Writer();
        $value->write($newWriter);
        $this->writeWireTypeData($writer, $newWriter->getData());
    }
    protected function checkTypeOfValueImpl($value) {
        $refObject = self::getReflectObject($value);
        $messageName = $refObject->getMethod('name')->invoke($value);
        if($this->extra === $messageName){
            return true;
        }
        $refClass = self::getReflectClass($this->extra);
        return $refClass->isInstance($value);
    }
    
    /**
     * @param object $value
     * @return ReflectionObject
     */
    protected static function getReflectObject($value){
        $hash = spl_object_hash($value);
        if(isset(self::$reflectObjectCache[$hash])){
            return self::$reflectObjectCache[$hash];
        }
        return self::$reflectObjectCache[$hash] = new ReflectionObject($value);
    }
    /**
     * @param string $className
     * @return reflectionClass
     */
    protected static function getReflectClass($className){
        if(isset(self::$reflectClass[$className])){
            return self::$reflectClass[$className];
        }
        return self::$reflectClass[$className] = new ReflectionClass($className);
    }
}
