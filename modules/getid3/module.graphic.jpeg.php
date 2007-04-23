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
// | module.graphic.jpeg.php                                              |
// | Module for analyzing JPEG graphic files.                             |
// | dependencies: exif support in PHP (optional)                         |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.jpeg.php,v 1.4 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_jpeg extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;

        $getid3->info['fileformat']                  = 'jpg';
        $getid3->info['video']['dataformat']         = 'jpg';
        $getid3->info['video']['lossless']           = false;
        $getid3->info['video']['bits_per_sample']    = 24;
        $getid3->info['video']['pixel_aspect_ratio'] = (float)1;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);

        list($getid3->info['video']['resolution_x'], $getid3->info['video']['resolution_y'], $type) = getimagesize($getid3->filename);
        
        if ($type != 2) {
            throw new getid3_exception('File detected as JPEG, but is currupt.');
        }

        if (function_exists('exif_read_data')) {

            $getid3->info['jpg']['exif'] = exif_read_data($getid3->filename, '', true, false);

        } else {

            $getid3->warning('EXIF parsing only available when compiled with --enable-exif (or php_exif.dll enabled for Windows).');
        }

        return true;
    }

}


?>