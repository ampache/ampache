<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 James Heinrich, Allan Hansen                 |
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
// | module.audio.dts.php                                                 |
// | Module for analyzing DTS audio files                                 |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.dts.php,v 1.2 2006/11/16 13:14:26 ah Exp $



// Specs taken from "DTS Coherent Acoustics;Core and Extensions,  ETSI TS 102 114 V1.2.1 (2002-12)"
// (http://pda.etsi.org/pda/queryform.asp)
// With thanks to Gambit <macteam@users.sourceforge.net> http://mac.sourceforge.net/atl/

class getid3_dts extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        $getid3->info['dts'] = array ();
        $info_dts = &$getid3->info['dts'];

        $getid3->info['fileformat'] = 'dts';

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $header = fread($getid3->fp, 16);

        $fhBS = getid3_lib::BigEndian2Bin(substr($header, 4, 12));
        $bs_offset = 0;
        $info_dts['raw']['frame_type']             =        bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['deficit_samples']        =        bindec(substr($fhBS, $bs_offset,  5)); $bs_offset +=  5;
        $info_dts['flags']['crc_present']          = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['pcm_sample_blocks']      =        bindec(substr($fhBS, $bs_offset,  7)); $bs_offset +=  7;
        $info_dts['raw']['frame_byte_size']        =        bindec(substr($fhBS, $bs_offset, 14)); $bs_offset += 14;
        $info_dts['raw']['channel_arrangement']    =        bindec(substr($fhBS, $bs_offset,  6)); $bs_offset +=  6;
        $info_dts['raw']['sample_frequency']       =        bindec(substr($fhBS, $bs_offset,  4)); $bs_offset +=  4;
        $info_dts['raw']['bitrate']                =        bindec(substr($fhBS, $bs_offset,  5)); $bs_offset +=  5;
        $info_dts['flags']['embedded_downmix']     = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['dynamicrange']         = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['timestamp']            = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['auxdata']              = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['hdcd']                 = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['extension_audio']        =        bindec(substr($fhBS, $bs_offset,  3)); $bs_offset +=  3;
        $info_dts['flags']['extended_coding']      = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['audio_sync_insertion'] = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['lfe_effects']            =        bindec(substr($fhBS, $bs_offset,  2)); $bs_offset +=  2;
        $info_dts['flags']['predictor_history']    = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        if ($info_dts['flags']['crc_present']) {
            $info_dts['raw']['crc16']              =        bindec(substr($fhBS, $bs_offset, 16)); $bs_offset += 16;
        }
        $info_dts['flags']['mri_perfect_reconst']  = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['encoder_soft_version']   =        bindec(substr($fhBS, $bs_offset,  4)); $bs_offset +=  4;
        $info_dts['raw']['copy_history']           =        bindec(substr($fhBS, $bs_offset,  2)); $bs_offset +=  2;
        $info_dts['raw']['bits_per_sample']        =        bindec(substr($fhBS, $bs_offset,  2)); $bs_offset +=  2;
        $info_dts['flags']['surround_es']          = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['front_sum_diff']       = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['flags']['surround_sum_diff']    = (bool) bindec(substr($fhBS, $bs_offset,  1)); $bs_offset +=  1;
        $info_dts['raw']['dialog_normalization']   =        bindec(substr($fhBS, $bs_offset,  4)); $bs_offset +=  4;


        $info_dts['bitrate']              = $this->DTSbitrateLookup($info_dts['raw']['bitrate']);
        $info_dts['bits_per_sample']      = $this->DTSbitPerSampleLookup($info_dts['raw']['bits_per_sample']);
        $info_dts['sample_rate']          = $this->DTSsampleRateLookup($info_dts['raw']['sample_frequency']);
        $info_dts['dialog_normalization'] = $this->DTSdialogNormalization($info_dts['raw']['dialog_normalization'], $info_dts['raw']['encoder_soft_version']);
        $info_dts['flags']['lossless']    = (($info_dts['raw']['bitrate'] == 31) ? true  : false);
        $info_dts['bitrate_mode']         = (($info_dts['raw']['bitrate'] == 30) ? 'vbr' : 'cbr');
        $info_dts['channels']             = $this->DTSnumChannelsLookup($info_dts['raw']['channel_arrangement']);
        $info_dts['channel_arrangement']  = $this->DTSchannelArrangementLookup($info_dts['raw']['channel_arrangement']);

        $getid3->info['audio']['dataformat']      = 'dts';
        $getid3->info['audio']['lossless']        = $info_dts['flags']['lossless'];
        $getid3->info['audio']['bitrate_mode']    = $info_dts['bitrate_mode'];
        $getid3->info['audio']['bits_per_sample'] = $info_dts['bits_per_sample'];
        $getid3->info['audio']['sample_rate']     = $info_dts['sample_rate'];
        $getid3->info['audio']['channels']        = $info_dts['channels'];
        $getid3->info['audio']['bitrate']         = $info_dts['bitrate'];
        $getid3->info['playtime_seconds']         = ($getid3->info['avdataend'] - $getid3->info['avdataoffset']) / ($info_dts['bitrate'] / 8);

        return true;
    }


    public static function DTSbitrateLookup($index) {

        static $lookup = array (
            0  => 32000,
            1  => 56000,
            2  => 64000,
            3  => 96000,
            4  => 112000,
            5  => 128000,
            6  => 192000,
            7  => 224000,
            8  => 256000,
            9  => 320000,
            10 => 384000,
            11 => 448000,
            12 => 512000,
            13 => 576000,
            14 => 640000,
            15 => 768000,
            16 => 960000,
            17 => 1024000,
            18 => 1152000,
            19 => 1280000,
            20 => 1344000,
            21 => 1408000,
            22 => 1411200,
            23 => 1472000,
            24 => 1536000,
            25 => 1920000,
            26 => 2048000,
            27 => 3072000,
            28 => 3840000,
            29 => 'open',
            30 => 'variable',
            31 => 'lossless'
        );
        return @$lookup[$index];
    }


    public static function DTSsampleRateLookup($index) {

        static $lookup = array (
            0  => 'invalid',
            1  => 8000,
            2  => 16000,
            3  => 32000,
            4  => 'invalid',
            5  => 'invalid',
            6  => 11025,
            7  => 22050,
            8  => 44100,
            9  => 'invalid',
            10 => 'invalid',
            11 => 12000,
            12 => 24000,
            13 => 48000,
            14 => 'invalid',
            15 => 'invalid'
        );
        return @$lookup[$index];
    }


    public static function DTSbitPerSampleLookup($index) {

        static $lookup = array (
            0  => 16,
            1  => 20,
            2  => 24,
            3  => 24,
        );
        return @$lookup[$index];
    }


    public static function DTSnumChannelsLookup($index) {

        switch ($index) {
            case 0:
                return 1;

            case 1:
            case 2:
            case 3:
            case 4:
                return 2;

            case 5:
            case 6:
                return 3;

            case 7:
            case 8:
                return 4;

            case 9:
                return 5;

            case 10:
            case 11:
            case 12:
                return 6;

            case 13:
                return 7;

            case 14:
            case 15:
                return 8;
        }
        return false;
    }


    public static function DTSchannelArrangementLookup($index) {

        static $lookup = array (
            0  => 'A',
            1  => 'A + B (dual mono)',
            2  => 'L + R (stereo)',
            3  => '(L+R) + (L-R) (sum-difference)',
            4  => 'LT + RT (left and right total)',
            5  => 'C + L + R',
            6  => 'L + R + S',
            7  => 'C + L + R + S',
            8  => 'L + R + SL + SR',
            9  => 'C + L + R + SL + SR',
            10 => 'CL + CR + L + R + SL + SR',
            11 => 'C + L + R+ LR + RR + OV',
            12 => 'CF + CR + LF + RF + LR + RR',
            13 => 'CL + C + CR + L + R + SL + SR',
            14 => 'CL + CR + L + R + SL1 + SL2 + SR1 + SR2',
            15 => 'CL + C+ CR + L + R + SL + S + SR',
        );
        return (@$lookup[$index] ? @$lookup[$index] : 'user-defined');
    }


    public static function DTSdialogNormalization($index, $version) {

        switch ($version) {
            case 7:
                return 0 - $index;

            case 6:
                return 0 - 16 - $index;
        }
        return false;
    }

}


?>