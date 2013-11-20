<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_WireType_LenghtDelimited implements PhpBuf_WireType_Interface {
    private function __construct() {}
    public static function read(PhpBuf_IO_Reader_Interface $reader) {
        $lenght = PhpBuf_Base128::decodeFromReader($reader);
        return $reader->getBytes($lenght);
    }
    public static function write(PhpBuf_IO_Writer_Interface $writer, $value) {
        PhpBuf_Base128::encodeToWriter($writer, strlen($value));
        $writer->writeBytes($value);
    }
}
