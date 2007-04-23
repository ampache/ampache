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
// | module.graphic.pcd.php                                               |
// | Module for analyzing PhotoCD (PCD) Image files.                      |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.pcd.php,v 1.2 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_pcd extends getid3_handler
{


    public function Analyze() {
        
        $getid3 = $this->getid3;

        $getid3->info['fileformat']          = 'pcd';
        $getid3->info['video']['dataformat'] = 'pcd';
        $getid3->info['video']['lossless']   = false;

        fseek($getid3->fp, $getid3->info['avdataoffset'] + 72, SEEK_SET);

        $pcd_flags       = fread($getid3->fp, 1);
        $pcd_is_vertical = ((ord($pcd_flags) & 0x01) ? true : false);

        if ($pcd_is_vertical) {
            $getid3->info['video']['resolution_x'] = 3072;
            $getid3->info['video']['resolution_y'] = 2048;
        } else {
            $getid3->info['video']['resolution_x'] = 2048;
            $getid3->info['video']['resolution_y'] = 3072;
        }

    }


}

?>