<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Field_Bool extends PhpBuf_Field_Abstract {
    protected $wireType = PhpBuf_WireType::WIRETYPE_VARINT;
    protected function readImpl(PhpBuf_IO_Reader_Interface $reader) {
        return (boolean)$this->readWireTypeData($reader);
    }
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        $this->writeWireTypeData($writer, (integer)$value);
    }
    protected function checkTypeOfValueImpl($value) {
        return is_bool($value);
    }
}
