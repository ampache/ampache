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
// | module.audio.mpc_old.php                                             |
// | Module for analyzing Musepack/MPEG+ Audio files - SV4-SV6            |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.mpc_old.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_mpc_old extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        // http://www.uni-jena.de/~pfk/mpp/sv8/header.html
        
        $getid3->info['mpc']['header'] = array ();
        $info_mpc_header = &$getid3->info['mpc']['header'];

        $getid3->info['fileformat']               = 'mpc';
        $getid3->info['audio']['dataformat']      = 'mpc';
        $getid3->info['audio']['bitrate_mode']    = 'vbr';
        $getid3->info['audio']['channels']        = 2;  // the format appears to be hardcoded for stereo only
        $getid3->info['audio']['lossless']        = false;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);

        $info_mpc_header['size'] = 8;
        $getid3->info['avdataoffset'] += $info_mpc_header['size'];
        
        $mpc_header_data = fread($getid3->fp, $info_mpc_header['size']);
            
        
        // Most of this code adapted from Jurgen Faul's MPEGplus source code - thanks Jurgen! :)
        $header_dword[0] = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 0, 4));
        $header_dword[1] = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 4, 4));


        // DDDD DDDD  CCCC CCCC  BBBB BBBB  AAAA AAAA
        // aaaa aaaa  abcd dddd  dddd deee  eeff ffff
        //
        // a = bitrate       = anything
        // b = IS            = anything
        // c = MS            = anything
        // d = streamversion = 0000000004 or 0000000005 or 0000000006
        // e = maxband       = anything
        // f = blocksize     = 000001 for SV5+, anything(?) for SV4

        $info_mpc_header['target_bitrate']       =       (($header_dword[0] & 0xFF800000) >> 23);
        $info_mpc_header['intensity_stereo']     = (bool)(($header_dword[0] & 0x00400000) >> 22);
        $info_mpc_header['mid-side_stereo']      = (bool)(($header_dword[0] & 0x00200000) >> 21);
        $info_mpc_header['stream_major_version'] =        ($header_dword[0] & 0x001FF800) >> 11;
        $info_mpc_header['stream_minor_version'] = 0; 
        $info_mpc_header['max_band']             =        ($header_dword[0] & 0x000007C0) >>  6;  // related to lowpass frequency, not sure how it translates exactly
        $info_mpc_header['block_size']           =        ($header_dword[0] & 0x0000003F);

        switch ($info_mpc_header['stream_major_version']) {
            case 4:
                $info_mpc_header['frame_count'] = ($header_dword[1] >> 16);
                break;
            case 5:
            case 6:
                $info_mpc_header['frame_count'] =  $header_dword[1];
                break;

            default:
                throw new getid3_exception('Expecting 4, 5 or 6 in version field, found '.$info_mpc_header['stream_major_version'].' instead');
        }

        if (($info_mpc_header['stream_major_version'] > 4) && ($info_mpc_header['block_size'] != 1)) {
            $getid3->warning('Block size expected to be 1, actual value found: '.$info_mpc_header['block_size']);
        }

        $info_mpc_header['sample_rate'] = $getid3->info['audio']['sample_rate'] = 44100; // AB: used by all files up to SV7
        $info_mpc_header['samples']     = $info_mpc_header['frame_count'] * 1152 * $getid3->info['audio']['channels'];

        $getid3->info['audio']['bitrate_mode'] = $info_mpc_header['target_bitrate'] == 0 ? 'vbr' : 'cbr';

        $getid3->info['mpc']['bitrate']   = ($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8 * 44100 / $info_mpc_header['frame_count'] / 1152;
        $getid3->info['audio']['bitrate'] = $getid3->info['mpc']['bitrate'];
        $getid3->info['audio']['encoder'] = 'SV'.$info_mpc_header['stream_major_version'];
        
        return true;
    }
    
}


?>