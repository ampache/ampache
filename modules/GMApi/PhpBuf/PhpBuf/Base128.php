<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://code.google.com/p/php-protobuf/
 *
 */
class PhpBuf_Base128 {

    private function __construct() {}
    /**
     * Encode value into varint string
     *
     * @param integer $value
     * @return string
     */
    public static function encode($value) {
        if(!is_integer($value) || $value < 0) {
            throw new PhpBuf_Base128_Exception("value mast be unsigned integer");
        }
        if($value <= 127) {
            return chr($value);
        }
        $result = '';
        $bin = decbin($value);
        $bit8 = '1';
        $index = strlen($bin);
        $substrLenght = 7;
        while (0 < $index) {
            if($index < 8) {
                $bit8 = '0';
            }
            $index = $index - 7;
            if($index < 0) {
                $substrLenght = $substrLenght + $index; $index = 0;
            }
            
            $bin7bit = substr($bin, $index, $substrLenght);
            $dec = bindec($bit8 . $bin7bit);
            $char = chr($dec);
            $result = $result . $char;
        }
        return $result;
    }
    
    /**
     * Encode value and write to PhpBuf_IO_Writer_Interface
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     */
    public static function encodeToWriter(PhpBuf_IO_Writer_Interface $writer, $value) {
        $writer->writeBytes(self::encode($value));
    }
    
    /**
     * Decode varint encoded string from PhpBuf_IO_Writer_Interface
     *
     * @param PhpBuf_IO_Reader_Interface $value
     * @return integer
     */
    public static function decodeFromReader(PhpBuf_IO_Reader_Interface $reader) {
        $continue = true;
        $result = '';
        while ($continue) {
            $byte = unpack('C', $reader->getByte());
            $bin = sprintf('%b', $byte[1]);
            if(strlen($bin) < 8) {
                $continue = false;
            }
            $bin = str_pad($bin, 8, '0', STR_PAD_LEFT);
            $bin7bit = substr($bin, 1, 7);
            $result = $bin7bit . $result;
        }
        return bindec($result);
    }
    
}
