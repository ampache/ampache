<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
interface PhpBuf_WireType_Interface {
    public static function read(PhpBuf_IO_Reader_Interface $reader);
    public static function write(PhpBuf_IO_Writer_Interface $writer, $value);
}