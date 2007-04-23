<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 James Heinrich, Allan Hansen                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2 of the GPL license,         |
// | that is bundled with this package in the file license.txt and is     |
// | available through the world-wide-web at the following url:           |
// | http://www.gnu.org/copyleft/gpl.html                                 |
// +----------------------------------------------------------------------+
// | getID3() - http://getid3.sourceforge.net or http://www.getid3.org    |
// +----------------------------------------------------------------------+
// | Authors: James Heinrich <infoØgetid3*org>                            |
// |          Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
// | module.lib.iconv_replacement.php                                     |
// | getID3() library file.                                               |
// | dependencies: NONE, required by getid3.php if no iconv() present.    |
// +----------------------------------------------------------------------+
//
// $Id: module.lib.iconv_replacement.php,v 1.4 2006/11/02 10:48:02 ah Exp $


class getid3_iconv_replacement
{

    public static function iconv($in_charset, $out_charset, $string) {

        if ($in_charset == $out_charset) {
            return $string;
        }
        
        static $supported_charsets = array (
            'ISO-8859-1'    => 'iso88591', 
            'UTF-8'         => 'utf8',
            'UTF-16BE'      => 'utf16be', 
            'UTF-16LE'      => 'utf16le', 
            'UTF-16'        => 'utf16'
        );

        // Convert
        $function_name = 'iconv_' . @$supported_charsets[$in_charset] . '_' . @$supported_charsets[$out_charset];
        
        if (is_callable(array('getid3_iconv_replacement', $function_name))) {
            return getid3_iconv_replacement::$function_name($string);
        }
        
        // Invalid charset used
        if (!@$supported_charsets[$in_charset]) {
            throw new getid3_exception('PHP does not have iconv() support - cannot use ' . $in_charset . ' charset.');
        }
        
        if (!@$supported_charsets[$out_charset]) {
            throw new getid3_exception('PHP does not have iconv() support - cannot use ' . $out_charset . ' charset.');
        }
    }



    public static function iconv_int_utf8($charval) {
        if ($charval < 128) {
            // 0bbbbbbb
            $newcharstring = chr($charval);
        } elseif ($charval < 2048) {
            // 110bbbbb 10bbbbbb
            $newcharstring  = chr(($charval >> 6) | 0xC0);
            $newcharstring .= chr(($charval & 0x3F) | 0x80);
        } elseif ($charval < 65536) {
            // 1110bbbb 10bbbbbb 10bbbbbb
            $newcharstring  = chr(($charval >> 12) | 0xE0);
            $newcharstring .= chr(($charval >>  6) | 0xC0);
            $newcharstring .= chr(($charval & 0x3F) | 0x80);
        } else {
            // 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
            $newcharstring  = chr(($charval >> 18) | 0xF0);
            $newcharstring .= chr(($charval >> 12) | 0xC0);
            $newcharstring .= chr(($charval >>  6) | 0xC0);
            $newcharstring .= chr(($charval & 0x3F) | 0x80);
        }
        return $newcharstring;
    }



    // ISO-8859-1 => UTF-8
    public static function iconv_iso88591_utf8($string, $bom=false) {
        if (function_exists('utf8_encode')) {
            return utf8_encode($string);
        }
        // utf8_encode() unavailable, use getID3()'s iconv() conversions (possibly PHP is compiled without XML support)
        $newcharstring = '';
        if ($bom) {
            $newcharstring .= "\xEF\xBB\xBF";
        }
        for ($i = 0; $i < strlen($string); $i++) {
            $charval = ord($string{$i});
            $newcharstring .= getid3_iconv_replacement::iconv_int_utf8($charval);
        }
        return $newcharstring;
    }



    // ISO-8859-1 => UTF-16BE
    public static function iconv_iso88591_utf16be($string, $bom=false) {
        $newcharstring = '';
        if ($bom) {
            $newcharstring .= "\xFE\xFF";
        }
        for ($i = 0; $i < strlen($string); $i++) {
            $newcharstring .= "\x00".$string{$i};
        }
        return $newcharstring;
    }



    // ISO-8859-1 => UTF-16LE
    public static function iconv_iso88591_utf16le($string, $bom=false) {
        $newcharstring = '';
        if ($bom) {
            $newcharstring .= "\xFF\xFE";
        }
        for ($i = 0; $i < strlen($string); $i++) {
            $newcharstring .= $string{$i}."\x00";
        }
        return $newcharstring;
    }



    // ISO-8859-1 => UTF-16
    public static function iconv_iso88591_utf16($string) {
        return getid3_lib::iconv_iso88591_utf16le($string, true);
    }



    // UTF-8 => ISO-8859-1
    public static function iconv_utf8_iso88591($string) {
        if (function_exists('utf8_decode')) {
            return utf8_decode($string);
        }
        // utf8_decode() unavailable, use getID3()'s iconv() conversions (possibly PHP is compiled without XML support)
        $newcharstring = '';
        $offset = 0;
        $stringlength = strlen($string);
        while ($offset < $stringlength) {
            if ((ord($string{$offset}) | 0x07) == 0xF7) {
                // 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
                           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
                           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 3)}) & 0x3F);
                $offset += 4;
            } elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
                // 1110bbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
                           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 2)}) & 0x3F);
                $offset += 3;
            } elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
                // 110bbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
                            (ord($string{($offset + 1)}) & 0x3F);
                $offset += 2;
            } elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
                // 0bbbbbbb
                $charval = ord($string{$offset});
                $offset += 1;
            } else {
                // error? throw some kind of warning here?
                $charval = false;
                $offset += 1;
            }
            if ($charval !== false) {
                $newcharstring .= (($charval < 256) ? chr($charval) : '?');
            }
        }
        return $newcharstring;
    }



    // UTF-8 => UTF-16BE
    public static function iconv_utf8_utf16be($string, $bom=false) {
        $newcharstring = '';
        if ($bom) {
            $newcharstring .= "\xFE\xFF";
        }
        $offset = 0;
        $stringlength = strlen($string);
        while ($offset < $stringlength) {
            if ((ord($string{$offset}) | 0x07) == 0xF7) {
                // 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
                           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
                           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 3)}) & 0x3F);
                $offset += 4;
            } elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
                // 1110bbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
                           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 2)}) & 0x3F);
                $offset += 3;
            } elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
                // 110bbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
                            (ord($string{($offset + 1)}) & 0x3F);
                $offset += 2;
            } elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
                // 0bbbbbbb
                $charval = ord($string{$offset});
                $offset += 1;
            } else {
                // error? throw some kind of warning here?
                $charval = false;
                $offset += 1;
            }
            if ($charval !== false) {
                $newcharstring .= (($charval < 65536) ? getid3_lib::BigEndian2String($charval, 2) : "\x00".'?');
            }
        }
        return $newcharstring;
    }



    // UTF-8 => UTF-16LE
    public static function iconv_utf8_utf16le($string, $bom=false) {
        $newcharstring = '';
        if ($bom) {
            $newcharstring .= "\xFF\xFE";
        }
        $offset = 0;
        $stringlength = strlen($string);
        while ($offset < $stringlength) {
            if ((ord($string{$offset}) | 0x07) == 0xF7) {
                // 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x07) << 18) &
                           ((ord($string{($offset + 1)}) & 0x3F) << 12) &
                           ((ord($string{($offset + 2)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 3)}) & 0x3F);
                $offset += 4;
            } elseif ((ord($string{$offset}) | 0x0F) == 0xEF) {
                // 1110bbbb 10bbbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x0F) << 12) &
                           ((ord($string{($offset + 1)}) & 0x3F) <<  6) &
                            (ord($string{($offset + 2)}) & 0x3F);
                $offset += 3;
            } elseif ((ord($string{$offset}) | 0x1F) == 0xDF) {
                // 110bbbbb 10bbbbbb
                $charval = ((ord($string{($offset + 0)}) & 0x1F) <<  6) &
                            (ord($string{($offset + 1)}) & 0x3F);
                $offset += 2;
            } elseif ((ord($string{$offset}) | 0x7F) == 0x7F) {
                // 0bbbbbbb
                $charval = ord($string{$offset});
                $offset += 1;
            } else {
                // error? maybe throw some warning here?
                $charval = false;
                $offset += 1;
            }
            if ($charval !== false) {
                $newcharstring .= (($charval < 65536) ? getid3_lib::LittleEndian2String($charval, 2) : '?'."\x00");
            }
        }
        return $newcharstring;
    }

    
    
    // UTF-8 => UTF-16
    public static function iconv_utf8_utf16($string) {
        return getid3_lib::iconv_utf8_utf16le($string, true);
    }

    
    
    // UTF-16BE => ISO-8859-1
    public static function iconv_utf16be_iso88591($string) {
        if (substr($string, 0, 2) == "\xFE\xFF") {
            // strip BOM
            $string = substr($string, 2);
        }
        $newcharstring = '';
        for ($i = 0; $i < strlen($string); $i += 2) {
            $charval = getid3_lib::BigEndian2Int(substr($string, $i, 2));
            $newcharstring .= (($charval < 256) ? chr($charval) : '?');
        }
        return $newcharstring;
    }

    
    
    // UTF-16BE => UTF-8
    public static function iconv_utf16be_utf8($string) {
        if (substr($string, 0, 2) == "\xFE\xFF") {
            // strip BOM
            $string = substr($string, 2);
        }
        $newcharstring = '';
        for ($i = 0; $i < strlen($string); $i += 2) {
            $charval = getid3_lib::BigEndian2Int(substr($string, $i, 2));
            $newcharstring .= getid3_iconv_replacement::iconv_int_utf8($charval);
        }
        return $newcharstring;
    }
    
    
    
    // UTF-16BE => UTF-16LE
    public static function iconv_utf16be_utf16le($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16le(getid3_iconv_replacement::iconv_utf16be_utf8($string));
    }
    
    
    
    // UTF-16BE => UTF-16
    public static function iconv_utf16be_utf16($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16(getid3_iconv_replacement::iconv_utf16be_utf8($string));
    }
    
    
    
    // UTF-16LE => ISO-8859-1
    public static function iconv_utf16le_iso88591($string) {
        if (substr($string, 0, 2) == "\xFF\xFE") {
            // strip BOM
            $string = substr($string, 2);
        }
        $newcharstring = '';
        for ($i = 0; $i < strlen($string); $i += 2) {
            $charval = getid3_lib::LittleEndian2Int(substr($string, $i, 2));
            $newcharstring .= (($charval < 256) ? chr($charval) : '?');
        }
        return $newcharstring;
    }

    
    
    // UTF-16LE => UTF-8
    public static function iconv_utf16le_utf8($string) {
        if (substr($string, 0, 2) == "\xFF\xFE") {
            // strip BOM
            $string = substr($string, 2);
        }
        $newcharstring = '';
        for ($i = 0; $i < strlen($string); $i += 2) {
            $charval = getid3_lib::LittleEndian2Int(substr($string, $i, 2));
            $newcharstring .= getid3_iconv_replacement::iconv_int_utf8($charval);
        }
        return $newcharstring;
    }

    
    
    // UTF-16LE => UTF-16BE
    public static function iconv_utf16le_utf16be($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16be(getid3_iconv_replacement::iconv_utf16le_utf8($string));
    }
    
    
    
    // UTF-16LE => UTF-16
    public static function iconv_utf16le_utf16($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16(getid3_iconv_replacement::iconv_utf16le_utf8($string));
    }
    
    
    
    // UTF-16 => ISO-8859-1
    public static function iconv_utf16_iso88591($string) {
        $bom = substr($string, 0, 2);
        if ($bom == "\xFE\xFF") {
            return getid3_lib::iconv_utf16be_iso88591(substr($string, 2));
        } elseif ($bom == "\xFF\xFE") {
            return getid3_lib::iconv_utf16le_iso88591(substr($string, 2));
        }
        return $string;
    }

    
    
    // UTF-16 => UTF-8
    public static function iconv_utf16_utf8($string) {
        $bom = substr($string, 0, 2);
        if ($bom == "\xFE\xFF") {
            return getid3_iconv_replacement::iconv_utf16be_utf8(substr($string, 2));
        } elseif ($bom == "\xFF\xFE") {
            return getid3_iconv_replacement::iconv_utf16le_utf8(substr($string, 2));
        }
        return $string;
    }
    
    
    
    // UTF-16 => UTF-16BE
    public static function iconv_utf16_utf16be($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16be(getid3_iconv_replacement::iconv_utf16_utf8($string));
    }
    
    
    
    // UTF-16 => UTF-16LE
    public static function iconv_utf16_utf16le($string) {
        return getid3_iconv_replacement::iconv_utf8_utf16le(getid3_iconv_replacement::iconv_utf16_utf8($string));
    }

}

?>