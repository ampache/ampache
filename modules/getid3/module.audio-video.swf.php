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
// | module.audio-video.swf.php                                           |
// | module for analyzing Macromedia Shockwave Flash files.               |
// | dependencies: zlib support in PHP                                    |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.swf.php,v 1.2 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_swf extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->info['fileformat']          = 'swf';
        $getid3->info['video']['dataformat'] = 'swf';

        // http://www.openswf.org/spec/SWFfileformat.html

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);

        $swf_file_data = fread($getid3->fp, $getid3->info['avdataend'] - $getid3->info['avdataoffset']); // 8 + 2 + 2 + max(9) bytes NOT including Frame_Size RECT data

        $getid3->info['swf']['header']['signature']   = substr($swf_file_data, 0, 3);
        switch ($getid3->info['swf']['header']['signature']) {
        
            case 'FWS':
                $getid3->info['swf']['header']['compressed'] = false;
                break;

            case 'CWS':
                $getid3->info['swf']['header']['compressed'] = true;
                break;

            default:
                throw new getid3_exception('Expecting "FWS" or "CWS" at offset '.$getid3->info['avdataoffset'].', found "'.$getid3->info['swf']['header']['signature'].'"');
        }
        $getid3->info['swf']['header']['version'] = getid3_lib::LittleEndian2Int($swf_file_data{3});
        $getid3->info['swf']['header']['length']  = getid3_lib::LittleEndian2Int(substr($swf_file_data, 4, 4));

        if (!function_exists('gzuncompress')) {
            throw new getid3_exception('getid3_swf requires --zlib support in PHP.');
        }

        if ($getid3->info['swf']['header']['compressed']) {

            if ($uncompressed_file_data = @gzuncompress(substr($swf_file_data, 8))) {
                $swf_file_data = substr($swf_file_data, 0, 8).$uncompressed_file_data;

            } else {
                throw new getid3_exception('Error decompressing compressed SWF data');
            }

        }

        $frame_size_bits_per_value = (ord(substr($swf_file_data, 8, 1)) & 0xF8) >> 3;
        $frame_size_data_length    = ceil((5 + (4 * $frame_size_bits_per_value)) / 8);
        $frame_size_data_string    = str_pad(decbin(ord($swf_file_data[8]) & 0x07), 3, '0', STR_PAD_LEFT);
        
        for ($i = 1; $i < $frame_size_data_length; $i++) {
            $frame_size_data_string .= str_pad(decbin(ord(substr($swf_file_data, 8 + $i, 1))), 8, '0', STR_PAD_LEFT);
        }
        
        list($x1, $x2, $y1, $y2) = explode("\n", wordwrap($frame_size_data_string, $frame_size_bits_per_value, "\n", 1));
        $getid3->info['swf']['header']['frame_width']  = bindec($x2);
        $getid3->info['swf']['header']['frame_height'] = bindec($y2);

        // http://www-lehre.informatik.uni-osnabrueck.de/~fbstark/diplom/docs/swf/Flash_Uncovered.htm
        // Next in the header is the frame rate, which is kind of weird.
        // It is supposed to be stored as a 16bit integer, but the first byte
        // (or last depending on how you look at it) is completely ignored.
        // Example: 0x000C  ->  0x0C  ->  12     So the frame rate is 12 fps.

        // Byte at (8 + $frame_size_data_length) is always zero and ignored
        $getid3->info['swf']['header']['frame_rate']  = getid3_lib::LittleEndian2Int($swf_file_data[9 + $frame_size_data_length]);
        $getid3->info['swf']['header']['frame_count'] = getid3_lib::LittleEndian2Int(substr($swf_file_data, 10 + $frame_size_data_length, 2));

        $getid3->info['video']['frame_rate']         = $getid3->info['swf']['header']['frame_rate'];
        $getid3->info['video']['resolution_x']       = intval(round($getid3->info['swf']['header']['frame_width']  / 20));
        $getid3->info['video']['resolution_y']       = intval(round($getid3->info['swf']['header']['frame_height'] / 20));
        $getid3->info['video']['pixel_aspect_ratio'] = (float)1;

        if (($getid3->info['swf']['header']['frame_count'] > 0) && ($getid3->info['swf']['header']['frame_rate'] > 0)) {
            $getid3->info['playtime_seconds'] = $getid3->info['swf']['header']['frame_count'] / $getid3->info['swf']['header']['frame_rate'];
        }


        // SWF tags

        $current_offset = 12 + $frame_size_data_length;
        $swf_data_length = strlen($swf_file_data);

        while ($current_offset < $swf_data_length) {

            $tag_ID_tag_length = getid3_lib::LittleEndian2Int(substr($swf_file_data, $current_offset, 2));
            $tag_ID     = ($tag_ID_tag_length & 0xFFFC) >> 6;
            $tag_length = ($tag_ID_tag_length & 0x003F);
            $current_offset += 2;
            if ($tag_length == 0x3F) {
                $tag_length = getid3_lib::LittleEndian2Int(substr($swf_file_data, $current_offset, 4));
                $current_offset += 4;
            }

            unset($tag_data);
            $tag_data['offset'] = $current_offset;
            $tag_data['size']   = $tag_length;
            $tag_data['id']     = $tag_ID;
            $tag_data['data']   = substr($swf_file_data, $current_offset, $tag_length);
            switch ($tag_ID) {
                
                case 0: // end of movie
                    break 2;

                case 9: // Set background color
                    $getid3->info['swf']['bgcolor'] = strtoupper(str_pad(dechex(getid3_lib::BigEndian2Int($tag_data['data'])), 6, '0', STR_PAD_LEFT));
                    break;

                default:
                    /*
                    if ($ReturnAllTagData) {
                        $getid3->info['swf']['tags'][] = $tag_data;
                    }
                    */
                    break;
            }

            $current_offset += $tag_length;
        }

        return true;
    }

}


?>