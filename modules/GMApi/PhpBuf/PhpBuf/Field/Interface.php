<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
interface PhpBuf_Field_Interface {
    public function read(PhpBuf_IO_Reader_Interface $reader);
    public function write(PhpBuf_IO_Writer_Interface $writer);
    public function setValue($value);
    public function getValue();
}
