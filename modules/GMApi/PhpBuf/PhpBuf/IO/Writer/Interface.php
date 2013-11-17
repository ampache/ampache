<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
interface PhpBuf_IO_Writer_Interface {
    public function writeByte($byte);
    public function writeBytes($bytes);
    public function getPosition();
    public function redo();
    public function getLenght();
    public function getData();
}