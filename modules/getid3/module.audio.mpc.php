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
// | module.audio.mpc.php                                                 |
// | Module for analyzing Musepack/MPEG+ Audio files                      |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.mpc.php,v 1.3 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_mpc extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        // http://www.uni-jena.de/~pfk/mpp/sv8/header.html

        $getid3->info['fileformat']            = 'mpc';
        $getid3->info['audio']['dataformat']   = 'mpc';
        $getid3->info['audio']['bitrate_mode'] = 'vbr';
        $getid3->info['audio']['channels']     = 2;  // the format appears to be hardcoded for stereo only
        $getid3->info['audio']['lossless']     = false;
        
        $getid3->info['mpc']['header'] = array ();
        $info_mpc_header = &$getid3->info['mpc']['header'];
        $info_mpc_header['size'] = 28;
        $info_mpc_header['raw']['preamble'] = 'MP+';    // Magic bytes
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $mpc_header_data = fread($getid3->fp, 28);
        
        $stream_version_byte = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 3, 1));
        $info_mpc_header['stream_major_version'] = ($stream_version_byte & 0x0F);
        $info_mpc_header['stream_minor_version'] = ($stream_version_byte & 0xF0) >> 4;
        if ($info_mpc_header['stream_major_version'] != 7) {
            throw new getid3_exception('Only Musepack SV7 supported');
        }
            
        $info_mpc_header['frame_count'] = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 4, 4));
        
        $info_mpc_header['raw']['title_peak']      = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 12, 2));
        $info_mpc_header['raw']['title_gain']      = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 14, 2), true);
        $info_mpc_header['raw']['album_peak']      = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 16, 2));
        $info_mpc_header['raw']['album_gain']      = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 18, 2), true);
        
        $info_mpc_header['raw']['not_sure_what']   = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 24, 3));
        $info_mpc_header['raw']['encoder_version'] = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 27, 1));
        
        $flags_dword1                              = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 8, 4));
        $flags_dword2                              = getid3_lib::LittleEndian2Int(substr($mpc_header_data, 20, 4));
        
        $info_mpc_header['intensity_stereo']   = (bool)(($flags_dword1 & 0x80000000) >> 31);
        $info_mpc_header['mid_side_stereo']    = (bool)(($flags_dword1 & 0x40000000) >> 30);
        $info_mpc_header['max_subband']        =         ($flags_dword1 & 0x3F000000) >> 24;
        $info_mpc_header['raw']['profile']     =         ($flags_dword1 & 0x00F00000) >> 20;
        $info_mpc_header['begin_loud']         = (bool)(($flags_dword1 & 0x00080000) >> 19);
        $info_mpc_header['end_loud']           = (bool)(($flags_dword1 & 0x00040000) >> 18);
        $info_mpc_header['raw']['sample_rate'] =         ($flags_dword1 & 0x00030000) >> 16;
        $info_mpc_header['max_level']          =         ($flags_dword1 & 0x0000FFFF);
        
        $info_mpc_header['true_gapless']       = (bool)(($flags_dword2 & 0x80000000) >> 31);
        $info_mpc_header['last_frame_length']  =         ($flags_dword2 & 0x7FF00000) >> 20;
        
        $info_mpc_header['profile']            = getid3_mpc::MPCprofileNameLookup($info_mpc_header['raw']['profile']);
        $info_mpc_header['sample_rate']        = getid3_mpc::MPCfrequencyLookup($info_mpc_header['raw']['sample_rate']);
        $getid3->info['audio']['sample_rate']  = $info_mpc_header['sample_rate'];
        $info_mpc_header['samples']            = ((($info_mpc_header['frame_count'] - 1) * 1152) + $info_mpc_header['last_frame_length']) * $getid3->info['audio']['channels'];

        $getid3->info['playtime_seconds'] = ($info_mpc_header['samples'] / $getid3->info['audio']['channels']) / $getid3->info['audio']['sample_rate'];

        $getid3->info['avdataoffset'] += $info_mpc_header['size'];
        
        $getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];

        $info_mpc_header['title_peak']    = $info_mpc_header['raw']['title_peak'];
        $info_mpc_header['title_peak_db'] = getid3_mpc::MPCpeakDBLookup($info_mpc_header['title_peak']);
        if ($info_mpc_header['raw']['title_gain'] < 0) {
            $info_mpc_header['title_gain_db'] = (float)(32768 + $info_mpc_header['raw']['title_gain']) / -100;
        } 
        else {
            $info_mpc_header['title_gain_db'] = (float)$info_mpc_header['raw']['title_gain'] / 100;
        }

        $info_mpc_header['album_peak']    = $info_mpc_header['raw']['album_peak'];
        $info_mpc_header['album_peak_db'] = getid3_mpc::MPCpeakDBLookup($info_mpc_header['album_peak']);
        if ($info_mpc_header['raw']['album_gain'] < 0) {
            $info_mpc_header['album_gain_db'] = (float)(32768 + $info_mpc_header['raw']['album_gain']) / -100;
        } 
        else {
            $info_mpc_header['album_gain_db'] = (float)$info_mpc_header['raw']['album_gain'] / 100;;
        }
        $info_mpc_header['encoder_version'] = getid3_mpc::MPCencoderVersionLookup($info_mpc_header['raw']['encoder_version']);

        $getid3->info['replay_gain']['track']['adjustment'] = $info_mpc_header['title_gain_db'];
        $getid3->info['replay_gain']['album']['adjustment'] = $info_mpc_header['album_gain_db'];

        if ($info_mpc_header['title_peak'] > 0) {
            $getid3->info['replay_gain']['track']['peak'] = $info_mpc_header['title_peak'];
        } 
        elseif (round($info_mpc_header['max_level'] * 1.18) > 0) {
            $getid3->info['replay_gain']['track']['peak'] = (int)(round($info_mpc_header['max_level'] * 1.18)); // why? I don't know - see mppdec.c
        }
        if ($info_mpc_header['album_peak'] > 0) {
            $getid3->info['replay_gain']['album']['peak'] = $info_mpc_header['album_peak'];
        }

        $getid3->info['audio']['encoder']         = $info_mpc_header['encoder_version'];
        $getid3->info['audio']['encoder_options'] = $info_mpc_header['profile'];
        
        return true;
    }



    public static function MPCprofileNameLookup($profileid) {
        
        static $lookup = array (
            0  => 'no profile',
            1  => 'Experimental',
            2  => 'unused',
            3  => 'unused',
            4  => 'unused',
            5  => 'below Telephone (q = 0.0)',
            6  => 'below Telephone (q = 1.0)',
            7  => 'Telephone (q = 2.0)',
            8  => 'Thumb (q = 3.0)',
            9  => 'Radio (q = 4.0)',
            10 => 'Standard (q = 5.0)',
            11 => 'Extreme (q = 6.0)',
            12 => 'Insane (q = 7.0)',
            13 => 'BrainDead (q = 8.0)',
            14 => 'above BrainDead (q = 9.0)',
            15 => 'above BrainDead (q = 10.0)'
        );
        return (isset($lookup[$profileid]) ? $lookup[$profileid] : 'invalid');
    }



    public static function MPCfrequencyLookup($frequencyid) {
        
        static $lookup = array (
            0 => 44100,
            1 => 48000,
            2 => 37800,
            3 => 32000
        );
        return (isset($lookup[$frequencyid]) ? $lookup[$frequencyid] : 'invalid');
    }



    public static function MPCpeakDBLookup($int_value) {
        
        if ($int_value > 0) {
            return ((log10($int_value) / log10(2)) - 15) * 6;
        }
        return false;
    }



    public static function MPCencoderVersionLookup($encoder_version) {
    
        //Encoder version * 100  (106 = 1.06)
        //EncoderVersion % 10 == 0        Release (1.0)
        //EncoderVersion %  2 == 0        Beta (1.06)
        //EncoderVersion %  2 == 1        Alpha (1.05a...z)

        if ($encoder_version == 0) {
            // very old version, not known exactly which
            return 'Buschmann v1.7.0-v1.7.9 or Klemm v0.90-v1.05';
        }

        if (($encoder_version % 10) == 0) {

            // release version
            return number_format($encoder_version / 100, 2);

        } elseif (($encoder_version % 2) == 0) {

            // beta version
            return number_format($encoder_version / 100, 2).' beta';

        }

        // alpha version
        return number_format($encoder_version / 100, 2).' alpha';
    }

}


?>