<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Field_Bytes extends PhpBuf_Field_Abstract {
    protected $wireType = PhpBuf_WireType::WIRETYPE_LENGTH_DELIMITED;
    protected function readImpl(PhpBuf_IO_Reader_Interface $reader) {
        return $this->readWireTypeData($reader);
    }
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        $this->writeWireTypeData($writer, $value);
    }
    protected function checkTypeOfValueImpl($value) {
        return is_string($value);
    }
}
