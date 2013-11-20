<?php

class PhpBuf_WireType {
    /**
     * Wire types
     *
     */
    const WIRETYPE_VARINT = 0;
    
    const WIRETYPE_FIXED64 = 1;
    
    const WIRETYPE_LENGTH_DELIMITED = 2;
    
    const WIRETYPE_START_GROUP = 3;
    
    const WIRETYPE_END_GROUP = 4;
    
    const WIRETYPE_FIXED32 = 5;

    /**
     * Valid wire types
     *
     * @var array
     */
    protected static $wireTypes = array(
        'Varint',
        'Fixed64',
        'LenghtDelimited',
        'StartGroup',
        'EndGroup',
        'Fixed32'
    );
    
    /**
     * Convert wire type id to wire type class name
     *
     * @param integer $id
     * @return string
     */
    public static function getWireTypeNameById($id) {
        return self::$wireTypes[$id];
    }
}