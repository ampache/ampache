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
// | module.archive.szip.php                                              |
// | module for analyzing SZIP compressed files                           |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.archive.szip.php,v 1.2 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_szip extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $szip_rkau = fread($getid3->fp, 6);
        
        // Magic bytes:  'SZ'."\x0A\x04"
            
        $getid3->info['fileformat']            = 'szip';

        $getid3->info['szip']['major_version'] = getid3_lib::BigEndian2Int(substr($szip_rkau, 4, 1));
        $getid3->info['szip']['minor_version'] = getid3_lib::BigEndian2Int(substr($szip_rkau, 5, 1));

        while (!feof($getid3->fp)) {
            $next_block_id = fread($getid3->fp, 2);
            switch ($next_block_id) {
                case 'SZ':
                    // Note that szip files can be concatenated, this has the same effect as
                    // concatenating the files. this also means that global header blocks
                    // might be present between directory/data blocks.
                    fseek($getid3->fp, 4, SEEK_CUR);
                    break;

                case 'BH':
                    $bh_header_bytes  = getid3_lib::BigEndian2Int(fread($getid3->fp, 3));
                    $bh_header_data   = fread($getid3->fp, $bh_header_bytes);
                    $bh_header_offset = 0;
                    while (strpos($bh_header_data, "\x00", $bh_header_offset) > 0) {
                        //filename as \0 terminated string  (empty string indicates end)
                        //owner as \0 terminated string (empty is same as last file)
                        //group as \0 terminated string (empty is same as last file)
                        //3 byte filelength in this block
                        //2 byte access flags
                        //4 byte creation time (like in unix)
                        //4 byte modification time (like in unix)
                        //4 byte access time (like in unix)

                        $bh_data_array['filename'] = substr($bh_header_data, $bh_header_offset, strcspn($bh_header_data, "\x00"));
                        $bh_header_offset += (strlen($bh_data_array['filename']) + 1);

                        $bh_data_array['owner'] = substr($bh_header_data, $bh_header_offset, strcspn($bh_header_data, "\x00"));
                        $bh_header_offset += (strlen($bh_data_array['owner']) + 1);

                        $bh_data_array['group'] = substr($bh_header_data, $bh_header_offset, strcspn($bh_header_data, "\x00"));
                        $bh_header_offset += (strlen($bh_data_array['group']) + 1);

                        $bh_data_array['filelength'] = getid3_lib::BigEndian2Int(substr($bh_header_data, $bh_header_offset, 3));
                        $bh_header_offset += 3;

                        $bh_data_array['access_flags'] = getid3_lib::BigEndian2Int(substr($bh_header_data, $bh_header_offset, 2));
                        $bh_header_offset += 2;

                        $bh_data_array['creation_time'] = getid3_lib::BigEndian2Int(substr($bh_header_data, $bh_header_offset, 4));
                        $bh_header_offset += 4;

                        $bh_data_array['modification_time'] = getid3_lib::BigEndian2Int(substr($bh_header_data, $bh_header_offset, 4));
                        $bh_header_offset += 4;

                        $bh_data_array['access_time'] = getid3_lib::BigEndian2Int(substr($bh_header_data, $bh_header_offset, 4));
                        $bh_header_offset += 4;

                        $getid3->info['szip']['BH'][] = $bh_data_array;
                    }
                    break;

                default:
                    break 2;
            }
        }

        return true;
    }

}

?>