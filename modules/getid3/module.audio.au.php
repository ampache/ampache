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
// | module.audio.au.php                                                  |
// | module for analyzing AU files                                        |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.au.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_au extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $au_header  = fread($getid3->fp, 8);

        // Magic bytes: .snd

        $getid3->info['au'] = array ();
        $info_au = &$getid3->info['au'];

        $getid3->info['fileformat']            = 'au';
        $getid3->info['audio']['dataformat']   = 'au';
        $getid3->info['audio']['bitrate_mode'] = 'cbr';
        $info_au['encoding']                   = 'ISO-8859-1';

        $info_au['header_length']   = getid3_lib::BigEndian2Int(substr($au_header,  4, 4));
        $au_header .= fread($getid3->fp, $info_au['header_length'] - 8);
        $getid3->info['avdataoffset'] += $info_au['header_length'];

        getid3_lib::ReadSequence('BigEndian2Int', $info_au, $au_header, 8,  
            array (
                'data_size'     => 4,
                'data_format_id'=> 4,
                'sample_rate'   => 4,
                'channels'      => 4
            )
        );
        $info_au['comments']['comment'][] = trim(substr($au_header, 24));

        $info_au['data_format']          = getid3_au::AUdataFormatNameLookup($info_au['data_format_id']);
        $info_au['used_bits_per_sample'] = getid3_au::AUdataFormatUsedBitsPerSampleLookup($info_au['data_format_id']);
        if ($info_au['bits_per_sample']  = getid3_au::AUdataFormatBitsPerSampleLookup($info_au['data_format_id'])) {
            $getid3->info['audio']['bits_per_sample'] = $info_au['bits_per_sample'];
        } else {
            unset($info_au['bits_per_sample']);
        }

        $getid3->info['audio']['sample_rate'] = $info_au['sample_rate'];
        $getid3->info['audio']['channels']    = $info_au['channels'];

        if (($getid3->info['avdataoffset'] + $info_au['data_size']) > $getid3->info['avdataend']) {
            $getid3->warning('Possible truncated file - expecting "'.$info_au['data_size'].'" bytes of audio data, only found '.($getid3->info['avdataend'] - $getid3->info['avdataoffset']).' bytes"');
        }

        $getid3->info['playtime_seconds'] = $info_au['data_size'] / ($info_au['sample_rate'] * $info_au['channels'] * ($info_au['used_bits_per_sample'] / 8));
        $getid3->info['audio']['bitrate'] = ($info_au['data_size'] * 8) / $getid3->info['playtime_seconds'];

        return true;
    }



    public static function AUdataFormatNameLookup($id) {
        
        static $lookup = array (
            0  => 'unspecified format',
            1  => '8-bit mu-law',
            2  => '8-bit linear',
            3  => '16-bit linear',
            4  => '24-bit linear',
            5  => '32-bit linear',
            6  => 'floating-point',
            7  => 'double-precision float',
            8  => 'fragmented sampled data',
            9  => 'SUN_FORMAT_NESTED',
            10 => 'DSP program',
            11 => '8-bit fixed-point',
            12 => '16-bit fixed-point',
            13 => '24-bit fixed-point',
            14 => '32-bit fixed-point',

            16 => 'non-audio display data',
            17 => 'SND_FORMAT_MULAW_SQUELCH',
            18 => '16-bit linear with emphasis',
            19 => '16-bit linear with compression',
            20 => '16-bit linear with emphasis + compression',
            21 => 'Music Kit DSP commands',
            22 => 'SND_FORMAT_DSP_COMMANDS_SAMPLES',
            23 => 'CCITT g.721 4-bit ADPCM',
            24 => 'CCITT g.722 ADPCM',
            25 => 'CCITT g.723 3-bit ADPCM',
            26 => 'CCITT g.723 5-bit ADPCM',
            27 => 'A-Law 8-bit'
        );
        
        return (isset($lookup[$id]) ? $lookup[$id] : false);
    }



    public static function AUdataFormatBitsPerSampleLookup($id) {
        
        static $lookup = array (
            1  => 8,
            2  => 8,
            3  => 16,
            4  => 24,
            5  => 32,
            6  => 32,
            7  => 64,

            11 => 8,
            12 => 16,
            13 => 24,
            14 => 32,

            18 => 16,
            19 => 16,
            20 => 16,

            23 => 16,

            25 => 16,
            26 => 16,
            27 => 8
        );
        return (isset($lookup[$id]) ? $lookup[$id] : false);
    }



    public static function AUdataFormatUsedBitsPerSampleLookup($id) {
        
        static $lookup = array (
            1  => 8,
            2  => 8,
            3  => 16,
            4  => 24,
            5  => 32,
            6  => 32,
            7  => 64,

            11 => 8,
            12 => 16,
            13 => 24,
            14 => 32,

            18 => 16,
            19 => 16,
            20 => 16,

            23 => 4,

            25 => 3,
            26 => 5,
            27 => 8,
        );
        return (isset($lookup[$id]) ? $lookup[$id] : false);
    }

}


?>