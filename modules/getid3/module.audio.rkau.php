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
// | module.audio.rkau.php                                                |
// | Module for analyzing RKAU Audio files                                |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.rkau.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_rkau extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $rkau_header = fread($getid3->fp, 20);
        
        // Magic bytes 'RKA'
            
        $getid3->info['fileformat']            = 'rkau';
        $getid3->info['audio']['dataformat']   = 'rkau';
        $getid3->info['audio']['bitrate_mode'] = 'vbr';
        
        // Shortcut
        $getid3->info['rkau'] = array ();
        $info_rkau            = &$getid3->info['rkau'];
        
        $info_rkau['raw']['version']   = getid3_lib::LittleEndian2Int(substr($rkau_header, 3, 1));
        $info_rkau['version']          = '1.'.str_pad($info_rkau['raw']['version'] & 0x0F, 2, '0', STR_PAD_LEFT);
        if (($info_rkau['version'] > 1.07) || ($info_rkau['version'] < 1.06)) {
            throw new getid3_exception('This version of getID3() can only parse RKAU files v1.06 and 1.07 (this file is v'.$info_rkau['version'].')');
        }

        getid3_lib::ReadSequence('LittleEndian2Int', $info_rkau, $rkau_header,  4,
            array (
                'source_bytes'     => 4,
                'sample_rate'      => 4,
                'channels'         => 1,
                'bits_per_sample'  => 1
            )
        );

        $info_rkau['raw']['quality']   = getid3_lib::LittleEndian2Int(substr($rkau_header, 14, 1));
        
        $quality =  $info_rkau['raw']['quality'] & 0x0F;

        $info_rkau['lossless']          = (($quality == 0) ? true : false);
        $info_rkau['compression_level'] = (($info_rkau['raw']['quality'] & 0xF0) >> 4) + 1;
        if (!$info_rkau['lossless']) {
            $info_rkau['quality_setting'] = $quality;
        }
        
        $info_rkau['raw']['flags']            = getid3_lib::LittleEndian2Int(substr($rkau_header, 15, 1));
        $info_rkau['flags']['joint_stereo']   = (bool)(!($info_rkau['raw']['flags'] & 0x01));
        $info_rkau['flags']['streaming']      =  (bool) ($info_rkau['raw']['flags'] & 0x02);
        $info_rkau['flags']['vrq_lossy_mode'] =  (bool) ($info_rkau['raw']['flags'] & 0x04);

        if ($info_rkau['flags']['streaming']) {
            $getid3->info['avdataoffset'] += 20;
            $info_rkau['compressed_bytes']  = getid3_lib::LittleEndian2Int(substr($rkau_header, 16, 4));
        } 
        else {
            $getid3->info['avdataoffset'] += 16;
            $info_rkau['compressed_bytes'] = $getid3->info['avdataend'] - $getid3->info['avdataoffset'] - 1;
        }
        // Note: compressed_bytes does not always equal what appears to be the actual number of compressed bytes,
        // sometimes it's more, sometimes less. No idea why(?)

        $getid3->info['audio']['lossless']        = $info_rkau['lossless'];
        $getid3->info['audio']['channels']        = $info_rkau['channels'];
        $getid3->info['audio']['bits_per_sample'] = $info_rkau['bits_per_sample'];
        $getid3->info['audio']['sample_rate']     = $info_rkau['sample_rate'];

        $getid3->info['playtime_seconds']         = $info_rkau['source_bytes'] / ($info_rkau['sample_rate'] * $info_rkau['channels'] * ($info_rkau['bits_per_sample'] / 8));
        $getid3->info['audio']['bitrate']         = ($info_rkau['compressed_bytes'] * 8) / $getid3->info['playtime_seconds'];

        return true;

    }

}

?>