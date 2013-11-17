<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
class PhpBuf_Type {
    const INT = 0;
    const SINT = 1;
    const BOOL = 2;
    const ENUM = 3;
    
    const STRING = 20;
    const BYTES = 21;
    
    const MESSAGE = 99;
    
    private static $types = array(
       0 => 'Int',
       1 => 'SInt',
       2 => 'Bool',
       3 => 'Enum',
       20 => 'String',
       21 => 'Bytes',
       99 => 'Message'
   );
                        
    public static function getNameById($id) {
        return self::$types[$id];
    }
}
