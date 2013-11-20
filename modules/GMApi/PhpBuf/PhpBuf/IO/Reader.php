<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_IO_Reader implements PhpBuf_IO_Reader_Interface {
    protected $data = null;
    protected $lenght = 0;
    protected $position = 0;
    protected $lastLenght = 0;
    public static function createFromWriter(PhpBuf_IO_Writer_Interface $writer) {
        return new PhpBuf_IO_Reader($writer->getData());
    }
    public function __construct($data) {
        $this->data = $data;
        $this->lenght = strlen($data);
    }
    public function getByte() {
        $this->check();
        $this->lastLenght = 1;
        return $this->data[$this->position++];
    }
    public function getBytes($lenght = 1) {
        $this->check($lenght);
        $returnData = substr($this->data, $this->position, $lenght);
        $this->position = $this->position + $lenght;
        $this->lastLenght = $lenght;
        return $returnData;
    }
    public function setPosition($position = 0) {
        $this->position = $position;
    }
    public function getPosition() {
        return $this->position;
    }
    public function next($steps = 1) {
        $this->position + $steps;
    }
    public function back() {
        $this->position--;
    }
    public function redo() {
        $this->position = $this->position - $this->lastLenght;
    }
    public function getLength(){
        return $this->lenght;
    }
    
    protected function check($lenght = 1) {
        if($this->lenght < ($this->position + $lenght)) {
            throw new PhpBuf_IO_Exception("end of data");
        }
    }
}
