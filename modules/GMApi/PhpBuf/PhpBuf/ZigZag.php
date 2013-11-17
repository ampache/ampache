<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_ZigZag {
    /**
     * Only as static class
     *
     */
    private function __construct() {}
    /**
     * ZigZag encoding
     *
     * @param integer $value
     * @return integer
     */
    public static function decode($value) {
        if(!is_integer($value) || $value < 0) {
            throw new PhpBuf_ZigZag_Exception("value mast be unsigned integer");
        }
        $result = round($value / 2);
        if(1 === abs($value % 2)) {
            $result = -($result);
        }
        return $result;
    }
    /**
     * ZigZag decoding
     *
     * @param integer $value
     * @return integer
     */
    public static function encode($value) {
        if(!is_integer($value)) {
            throw new PhpBuf_ZigZag_Exception("value mast be integer");
        }
        if($value >= 0) {
            return $value * 2;
        }
        return abs($value) * 2 - 1;
    }
}
