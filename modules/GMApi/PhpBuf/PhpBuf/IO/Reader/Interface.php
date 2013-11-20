<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
interface PhpBuf_IO_Reader_Interface {
    public static function createFromWriter(PhpBuf_IO_Writer_Interface $writer);
    public function getByte();
    public function getBytes($lengnt = 1);
    public function setPosition($position = 0);
    public function getPosition();
    public function next($steps = 1);
    public function redo();
    public function getLength();
}