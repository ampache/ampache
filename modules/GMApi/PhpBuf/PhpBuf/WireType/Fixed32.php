<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_WireType_Fixed32 implements PhpBuf_WireType_Interface {
    private function __construct() {}
    public static function read(PhpBuf_IO_Reader_Interface $reader) {
        throw new PhpBuf_NotImplemented_Exception("reader for PhpBuf_WireType_Fixed32 not implemented");
    }
    public static function write(PhpBuf_IO_Writer_Interface $writer, $data) {
        throw new PhpBuf_NotImplemented_Exception("writer for PhpBuf_WireType_Fixed32 not implemented");
    }
}
