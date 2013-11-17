<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Field_Enum extends PhpBuf_Field_Abstract {
    protected $wireType = PhpBuf_WireType::WIRETYPE_VARINT;
    protected function readImpl(PhpBuf_IO_Reader_Interface $reader) {
        if(false === $this->value) {
            throw new PhpBuf_Field_Exception("Unknow value in enum");
        }
        return $this->getEnumNameById($this->readWireTypeData($reader));
    }
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        $value = $this->getEnumIdByName($value);
        if(false === $value) {
            throw new PhpBuf_Field_Exception("Unknow value in enum");
        }
        $this->writeWireTypeData($writer, $value);
    }
    protected function getEnumNameById($id) {
        if(isset($this->extra[$id])) {
            return $this->extra[$id];
        }
        return false;
    }
    protected function getEnumIdByName($name) {
        $enums = array_flip($this->extra);
        if(isset($enums[$name])) {
            return $enums[$name];
        }
        return false;
    }
    protected function checkTypeOfValueImpl($value) {
        $enums = array_flip($this->extra);
        return isset($enums[$value]);
    }
}
