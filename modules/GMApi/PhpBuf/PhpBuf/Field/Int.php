<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Field_Int extends PhpBuf_Field_Abstract {
    protected $wireType = PhpBuf_WireType::WIRETYPE_VARINT;
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Reader_Interface $reader
     * @return unknown
     */
    protected function  readImpl(PhpBuf_IO_Reader_Interface $reader) {
        return $this->readWireTypeData($reader);
    }
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     * @param unknown_type $value
     */
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        $this->writeWireTypeData($writer, $value);
    }
    /**
     * Enter description here...
     *
     * @param unknown_type $value
     * @return unknown
     */
    protected function checkTypeOfValueImpl($value) {
        return is_integer($value);
    }
}
