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
// | module.graphic.gif.php                                               |
// | Module for analyzing CompuServe GIF graphic files.                   |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.gif.php,v 1.2 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_gif extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;

        $getid3->info['fileformat']                  = 'gif';
        $getid3->info['video']['dataformat']         = 'gif';
        $getid3->info['video']['lossless']           = true;
        $getid3->info['video']['pixel_aspect_ratio'] = (float)1;
        
        $getid3->info['gif']['header'] = array ();
        $info_gif_header = &$getid3->info['gif']['header'];

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $gif_header = fread($getid3->fp, 13);

        // Magic bytes
        $info_gif_header['raw']['identifier'] = 'GIF';
        
        getid3_lib::ReadSequence('LittleEndian2Int', $info_gif_header['raw'], $gif_header, 3,
            array (
                'version'        => -3,      // string
                'width'          => 2,
                'height'         => 2,
                'flags'          => 1,
                'bg_color_index' => 1,
                'aspect_ratio'   => 1
            )
        );

        $getid3->info['video']['resolution_x'] = $info_gif_header['raw']['width'];
        $getid3->info['video']['resolution_y'] = $info_gif_header['raw']['height'];
        $getid3->info['gif']['version']        = $info_gif_header['raw']['version'];
        
        $info_gif_header['flags']['global_color_table'] = (bool)($info_gif_header['raw']['flags'] & 0x80);
        
        if ($info_gif_header['raw']['flags'] & 0x80) {
            // Number of bits per primary color available to the original image, minus 1
            $info_gif_header['bits_per_pixel']  = 3 * ((($info_gif_header['raw']['flags'] & 0x70) >> 4) + 1);
        } else {
            $info_gif_header['bits_per_pixel']  = 0;
        }
        
        $info_gif_header['flags']['global_color_sorted'] = (bool)($info_gif_header['raw']['flags'] & 0x40);
        if ($info_gif_header['flags']['global_color_table']) {
            // the number of bytes contained in the Global Color Table. To determine that
            // actual size of the color table, raise 2 to [the value of the field + 1]
            $info_gif_header['global_color_size'] = pow(2, ($info_gif_header['raw']['flags'] & 0x07) + 1);
            $getid3->info['video']['bits_per_sample']           = ($info_gif_header['raw']['flags'] & 0x07) + 1;
        } else {
            $info_gif_header['global_color_size'] = 0;
        }
        
        if ($info_gif_header['raw']['aspect_ratio'] != 0) {
            // Aspect Ratio = (Pixel Aspect Ratio + 15) / 64
            $info_gif_header['aspect_ratio'] = ($info_gif_header['raw']['aspect_ratio'] + 15) / 64;
        }

        return true;
    }

}


?>