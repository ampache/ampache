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
// | module.lib.data-hash.php                                             |
// | getID3() library file.                                               |
// | dependencies: NONE.                                                  |
// +----------------------------------------------------------------------+
//
// $Id: module.lib.image_size.php,v 1.2 2006/11/02 10:48:02 ah Exp $



class getid3_lib_image_size
{
    
    const GIF_SIG   = "\x47\x49\x46";                           // 'GIF'
    const PNG_SIG   = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
    const JPG_SIG   = "\xFF\xD8\xFF";
    const JPG_SOS   = "\xDA";                                   // Start Of Scan - image data start
    const JPG_SOF0  = "\xC0";                                   // Start Of Frame N
    const JPG_SOF1  = "\xC1";                                   // N indicates which compression process
    const JPG_SOF2  = "\xC2";                                   // Only SOF0-SOF2 are now in common use
    const JPG_SOF3  = "\xC3";                                   // NB: codes C4 and CC are *not* SOF markers
    const JPG_SOF5  = "\xC5";                                   
    const JPG_SOF6  = "\xC6";                                   
    const JPG_SOF7  = "\xC7";                                   
    const JPG_SOF9  = "\xC9";                                   
    const JPG_SOF10 = "\xCA";                                   
    const JPG_SOF11 = "\xCB";                                   // NB: codes C4 and CC are *not* SOF markers
    const JPG_SOF13 = "\xCD";                                   
    const JPG_SOF14 = "\xCE";                                   
    const JPG_SOF15 = "\xCF";                                   
    const JPG_EOI   = "\xD9";                                   // End Of Image (end of datastream)


    static public function get($img_data) {

        $height = $width  = $type   = '';
        
        if ((substr($img_data, 0, 3) == getid3_lib_image_size::GIF_SIG) && (strlen($img_data) > 10)) {
            
            $dim = unpack('v2dim', substr($img_data, 6, 4));
            $width  = $dim['dim1'];
            $height = $dim['dim2'];
            $type = 1;
            
        } elseif ((substr($img_data, 0, 8) == getid3_lib_image_size::PNG_SIG) && (strlen($img_data) > 24)) {
            
            $dim = unpack('N2dim', substr($img_data, 16, 8));
            $width  = $dim['dim1'];
            $height = $dim['dim2'];
            $type = 3;
            
        } elseif ((substr($img_data, 0, 3) == getid3_lib_image_size::JPG_SIG) && (strlen($img_data) > 4)) {
            
            ///////////////// JPG CHUNK SCAN ////////////////////
            $img_pos = $type = 2;
            $buffer = strlen($img_data) - 2;
            while ($img_pos < strlen($img_data)) {
            
                // synchronize to the marker 0xFF
                $img_pos = strpos($img_data, 0xFF, $img_pos) + 1;
                $marker = $img_data[$img_pos];
                do {
                    $marker = ord($img_data[$img_pos++]);
                } while ($marker == 255);
            
                // find dimensions of block
                switch (chr($marker)) {
            
                    // Grab width/height from SOF segment (these are acceptable chunk types)
                    case getid3_lib_image_size::JPG_SOF0:
                    case getid3_lib_image_size::JPG_SOF1:
                    case getid3_lib_image_size::JPG_SOF2:
                    case getid3_lib_image_size::JPG_SOF3:
                    case getid3_lib_image_size::JPG_SOF5:
                    case getid3_lib_image_size::JPG_SOF6:
                    case getid3_lib_image_size::JPG_SOF7:
                    case getid3_lib_image_size::JPG_SOF9:
                    case getid3_lib_image_size::JPG_SOF10:
                    case getid3_lib_image_size::JPG_SOF11:
                    case getid3_lib_image_size::JPG_SOF13:
                    case getid3_lib_image_size::JPG_SOF14:
                    case getid3_lib_image_size::JPG_SOF15:
                        $dim = unpack('n2dim', substr($img_data, $img_pos + 3, 4));
                        $height = $dim['dim1'];
                        $width  = $dim['dim2'];
                        break 2; // found it so exit
            
                    case getid3_lib_image_size::JPG_EOI:
                    case getid3_lib_image_size::JPG_SOS:
                        return false;      
            
                    default:   // We're not interested in other markers
                        $skiplen = (ord($img_data[$img_pos++]) << 8) + ord($img_data[$img_pos++]) - 2;
                        // if the skip is more than what we've read in, read more
                        $buffer -= $skiplen;
                        if ($buffer < 512) { // if the buffer of data is too low, read more file.
                            return false; 
                        }
                        $img_pos += $skiplen;
                        break;
                } 
            } 
        } 

        return array ($width, $height, $type);
    } // end function


}

?>