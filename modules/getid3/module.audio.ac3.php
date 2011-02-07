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
// | module.audio.ac3.php                                                 |
// | Module for analyzing AC-3 (aka Dolby Digital) audio files            |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.ac3.php,v 1.3 2006/11/02 10:48:01 ah Exp $



class getid3_ac3 extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        // http://www.atsc.org/standards/a_52a.pdf

        $getid3->info['fileformat']            = 'ac3';
        $getid3->info['audio']['dataformat']   = 'ac3';
        $getid3->info['audio']['bitrate_mode'] = 'cbr';
        $getid3->info['audio']['lossless']     = false;

        $getid3->info['ac3']['raw']['bsi'] = array ();
        $info_ac3         = &$getid3->info['ac3'];
        $info_ac3_raw     = &$info_ac3['raw'];
        $info_ac3_raw_bsi = &$info_ac3_raw['bsi'];


        // An AC-3 serial coded audio bit stream is made up of a sequence of synchronization frames
        // Each synchronization frame contains 6 coded audio blocks (AB), each of which represent 256
        // new audio samples per channel. A synchronization information (SI) header at the beginning
        // of each frame contains information needed to acquire and maintain synchronization. A
        // bit stream information (BSI) header follows SI, and contains parameters describing the coded
        // audio service. The coded audio blocks may be followed by an auxiliary data (Aux) field. At the
        // end of each frame is an error check field that includes a CRC word for error detection. An
        // additional CRC word is located in the SI header, the use of which, by a decoder, is optional.
        //
        // syncinfo() | bsi() | AB0 | AB1 | AB2 | AB3 | AB4 | AB5 | Aux | CRC

        $this->fseek($getid3->info['avdataoffset'], SEEK_SET);
        $ac3_header['syncinfo'] = $this->fread(5);
        $info_ac3_raw['synchinfo']['synchword'] = substr($ac3_header['syncinfo'], 0, 2);

        if ($info_ac3_raw['synchinfo']['synchword'] != "\x0B\x77") {
            throw new getid3_exception('Expecting "\x0B\x77" at offset '.$getid3->info['avdataoffset'].', found \x'.strtoupper(dechex($ac3_header['syncinfo']{0})).'\x'.strtoupper(dechex($ac3_header['syncinfo']{1})).' instead');
        }


        // syncinfo() {
        //      syncword    16
        //      crc1        16
        //      fscod        2
        //      frmsizecod   6
        // } /* end of syncinfo */

        $info_ac3_raw['synchinfo']['crc1']       = getid3_lib::LittleEndian2Int(substr($ac3_header['syncinfo'], 2, 2));
        $ac3_synchinfo_fscod_frmsizecod          = getid3_lib::LittleEndian2Int(substr($ac3_header['syncinfo'], 4, 1));
        $info_ac3_raw['synchinfo']['fscod']      = ($ac3_synchinfo_fscod_frmsizecod & 0xC0) >> 6;
        $info_ac3_raw['synchinfo']['frmsizecod'] = ($ac3_synchinfo_fscod_frmsizecod & 0x3F);

        $info_ac3['sample_rate'] = getid3_ac3::AC3sampleRateCodeLookup($info_ac3_raw['synchinfo']['fscod']);
        if ($info_ac3_raw['synchinfo']['fscod'] <= 3) {
            $getid3->info['audio']['sample_rate'] = $info_ac3['sample_rate'];
        }

        $info_ac3['frame_length'] = getid3_ac3::AC3frameSizeLookup($info_ac3_raw['synchinfo']['frmsizecod'], $info_ac3_raw['synchinfo']['fscod']);
        $info_ac3['bitrate']      = getid3_ac3::AC3bitrateLookup($info_ac3_raw['synchinfo']['frmsizecod']);
        $getid3->info['audio']['bitrate'] = $info_ac3['bitrate'];

        $ac3_header['bsi'] = getid3_lib::BigEndian2Bin($this->fread(15));

        $info_ac3_raw_bsi['bsid'] = bindec(substr($ac3_header['bsi'], 0, 5));
        if ($info_ac3_raw_bsi['bsid'] > 8) {
            // Decoders which can decode version 8 will thus be able to decode version numbers less than 8.
            // If this standard is extended by the addition of additional elements or features, a value of bsid greater than 8 will be used.
            // Decoders built to this version of the standard will not be able to decode versions with bsid greater than 8.
            throw new getid3_exception('Bit stream identification is version '.$info_ac3_raw_bsi['bsid'].', but getID3() only understands up to version 8');
        }

        $info_ac3_raw_bsi['bsmod'] = bindec(substr($ac3_header['bsi'], 5, 3));
        $info_ac3_raw_bsi['acmod'] = bindec(substr($ac3_header['bsi'], 8, 3));

        $info_ac3['service_type'] = getid3_ac3::AC3serviceTypeLookup($info_ac3_raw_bsi['bsmod'], $info_ac3_raw_bsi['acmod']);
        $ac3_coding_mode = getid3_ac3::AC3audioCodingModeLookup($info_ac3_raw_bsi['acmod']);
        foreach($ac3_coding_mode as $key => $value) {
            $info_ac3[$key] = $value;
        }
        switch ($info_ac3_raw_bsi['acmod']) {
            case 0:
            case 1:
                $getid3->info['audio']['channelmode'] = 'mono';
                break;
            case 3:
            case 4:
                $getid3->info['audio']['channelmode'] = 'stereo';
                break;
            default:
                $getid3->info['audio']['channelmode'] = 'surround';
                break;
        }
        $getid3->info['audio']['channels'] = $info_ac3['num_channels'];

        $offset = 11;

        if ($info_ac3_raw_bsi['acmod'] & 0x01) {
            // If the lsb of acmod is a 1, center channel is in use and cmixlev follows in the bit stream.
            $info_ac3_raw_bsi['cmixlev'] = bindec(substr($ac3_header['bsi'], $offset, 2));
            $info_ac3['center_mix_level'] = getid3_ac3::AC3centerMixLevelLookup($info_ac3_raw_bsi['cmixlev']);
            $offset += 2;
        }

        if ($info_ac3_raw_bsi['acmod'] & 0x04) {
            // If the msb of acmod is a 1, surround channels are in use and surmixlev follows in the bit stream.
            $info_ac3_raw_bsi['surmixlev'] = bindec(substr($ac3_header['bsi'], $offset, 2));
            $info_ac3['surround_mix_level'] = getid3_ac3::AC3surroundMixLevelLookup($info_ac3_raw_bsi['surmixlev']);
            $offset += 2;
        }

        if ($info_ac3_raw_bsi['acmod'] == 0x02) {
            // When operating in the two channel mode, this 2-bit code indicates whether or not the program has been encoded in Dolby Surround.
            $info_ac3_raw_bsi['dsurmod'] = bindec(substr($ac3_header['bsi'], $offset, 2));
            $info_ac3['dolby_surround_mode'] = getid3_ac3::AC3dolbySurroundModeLookup($info_ac3_raw_bsi['dsurmod']);
            $offset += 2;
        }

        $info_ac3_raw_bsi['lfeon'] = $ac3_header['bsi']{$offset++} == '1';
        $info_ac3['lfe_enabled'] = $info_ac3_raw_bsi['lfeon'];
        if ($info_ac3_raw_bsi['lfeon']) {
            $getid3->info['audio']['channels'] .= '.1';
        }

        $info_ac3['channels_enabled'] = getid3_ac3::AC3channelsEnabledLookup($info_ac3_raw_bsi['acmod'], $info_ac3_raw_bsi['lfeon']);

        // This indicates how far the average dialogue level is below digital 100 percent. Valid values are 1–31.
        // The value of 0 is reserved. The values of 1 to 31 are interpreted as -1 dB to -31 dB with respect to digital 100 percent.
        $info_ac3_raw_bsi['dialnorm'] = bindec(substr($ac3_header['bsi'], $offset, 5));
        $offset += 5;
        $info_ac3['dialogue_normalization'] = '-'.$info_ac3_raw_bsi['dialnorm'].'dB';

        $info_ac3_raw_bsi['compre_flag'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['compre_flag']) {
            $info_ac3_raw_bsi['compr'] = bindec(substr($ac3_header['bsi'], $offset, 8));
            $offset += 8;

            $info_ac3['heavy_compression'] = getid3_ac3::AC3heavyCompression($info_ac3_raw_bsi['compr']);
        }

        $info_ac3_raw_bsi['langcode_flag'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['langcode_flag']) {
            $info_ac3_raw_bsi['langcod'] = bindec(substr($ac3_header['bsi'], $offset, 8));
            $offset += 8;
        }

        $info_ac3_raw_bsi['audprodie'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['audprodie']) {
            $info_ac3_raw_bsi['mixlevel'] = bindec(substr($ac3_header['bsi'], $offset, 5));
            $offset += 5;

            $info_ac3_raw_bsi['roomtyp']  = bindec(substr($ac3_header['bsi'], $offset, 2));
            $offset += 2;

            $info_ac3['mixing_level'] = (80 + $info_ac3_raw_bsi['mixlevel']).'dB';
            $info_ac3['room_type']    = getid3_ac3::AC3roomTypeLookup($info_ac3_raw_bsi['roomtyp']);
        }

        if ($info_ac3_raw_bsi['acmod'] == 0x00) {
            // If acmod is 0, then two completely independent program channels (dual mono)
            // are encoded into the bit stream, and are referenced as Ch1, Ch2. In this case,
            // a number of additional items are present in BSI or audblk to fully describe Ch2.


            // This indicates how far the average dialogue level is below digital 100 percent. Valid values are 1–31.
            // The value of 0 is reserved. The values of 1 to 31 are interpreted as -1 dB to -31 dB with respect to digital 100 percent.
            $info_ac3_raw_bsi['dialnorm2'] = bindec(substr($ac3_header['bsi'], $offset, 5));
            $offset += 5;

            $info_ac3['dialogue_normalization2'] = '-'.$info_ac3_raw_bsi['dialnorm2'].'dB';

            $info_ac3_raw_bsi['compre_flag2'] = $ac3_header['bsi']{$offset++} == '1';
            if ($info_ac3_raw_bsi['compre_flag2']) {
                $info_ac3_raw_bsi['compr2'] = bindec(substr($ac3_header['bsi'], $offset, 8));
                $offset += 8;

                $info_ac3['heavy_compression2'] = getid3_ac3::AC3heavyCompression($info_ac3_raw_bsi['compr2']);
            }

            $info_ac3_raw_bsi['langcode_flag2'] = $ac3_header['bsi']{$offset++} == '1';
            if ($info_ac3_raw_bsi['langcode_flag2']) {
                $info_ac3_raw_bsi['langcod2'] = bindec(substr($ac3_header['bsi'], $offset, 8));
                $offset += 8;
            }

            $info_ac3_raw_bsi['audprodie2'] = $ac3_header['bsi']{$offset++} == '1';
            if ($info_ac3_raw_bsi['audprodie2']) {
                $info_ac3_raw_bsi['mixlevel2'] = bindec(substr($ac3_header['bsi'], $offset, 5));
                $offset += 5;

                $info_ac3_raw_bsi['roomtyp2']  = bindec(substr($ac3_header['bsi'], $offset, 2));
                $offset += 2;

                $info_ac3['mixing_level2'] = (80 + $info_ac3_raw_bsi['mixlevel2']).'dB';
                $info_ac3['room_type2']    = getid3_ac3::AC3roomTypeLookup($info_ac3_raw_bsi['roomtyp2']);
            }

        }

        $info_ac3_raw_bsi['copyright'] = $ac3_header['bsi']{$offset++} == '1';

        $info_ac3_raw_bsi['original']  = $ac3_header['bsi']{$offset++} == '1';

        $info_ac3_raw_bsi['timecode1_flag'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['timecode1_flag']) {
            $info_ac3_raw_bsi['timecode1'] = bindec(substr($ac3_header['bsi'], $offset, 14));
            $offset += 14;
        }

        $info_ac3_raw_bsi['timecode2_flag'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['timecode2_flag']) {
            $info_ac3_raw_bsi['timecode2'] = bindec(substr($ac3_header['bsi'], $offset, 14));
            $offset += 14;
        }

        $info_ac3_raw_bsi['addbsi_flag'] = $ac3_header['bsi']{$offset++} == '1';
        if ($info_ac3_raw_bsi['addbsi_flag']) {
            $info_ac3_raw_bsi['addbsi_length'] = bindec(substr($ac3_header['bsi'], $offset, 6));
            $offset += 6;

            $ac3_header['bsi'] .= getid3_lib::BigEndian2Bin($this->fread($info_ac3_raw_bsi['addbsi_length']));

            $info_ac3_raw_bsi['addbsi_data'] = substr($ac3_header['bsi'], 119, $info_ac3_raw_bsi['addbsi_length'] * 8);
        }

        return true;
    }



    public static function AC3sampleRateCodeLookup($fscod) {

        static $lookup = array (
            0 => 48000,
            1 => 44100,
            2 => 32000,
            3 => 'reserved' // If the reserved code is indicated, the decoder should not attempt to decode audio and should mute.
        );
        return (isset($lookup[$fscod]) ? $lookup[$fscod] : false);
    }



    public static function AC3serviceTypeLookup($bsmod, $acmod) {

        static $lookup = array (
            0 => 'main audio service: complete main (CM)',
            1 => 'main audio service: music and effects (ME)',
            2 => 'associated service: visually impaired (VI)',
            3 => 'associated service: hearing impaired (HI)',
            4 => 'associated service: dialogue (D)',
            5 => 'associated service: commentary (C)',
            6 => 'associated service: emergency (E)',
            7 => 'main audio service: karaoke'
        );

        if ($bsmod == 7  &&  $acmod == 1) {
            return 'associated service: voice over (VO)';
        }

        return (isset($lookup[$bsmod]) ? $lookup[$bsmod] : false);
    }



    public static function AC3audioCodingModeLookup($acmod) {

        // array (channel configuration, # channels (not incl LFE), channel order)
        static $lookup = array (
            0 => array ('channel_config'=>'1+1', 'num_channels'=>2, 'channel_order'=>'Ch1,Ch2'),
            1 => array ('channel_config'=>'1/0', 'num_channels'=>1, 'channel_order'=>'C'),
            2 => array ('channel_config'=>'2/0', 'num_channels'=>2, 'channel_order'=>'L,R'),
            3 => array ('channel_config'=>'3/0', 'num_channels'=>3, 'channel_order'=>'L,C,R'),
            4 => array ('channel_config'=>'2/1', 'num_channels'=>3, 'channel_order'=>'L,R,S'),
            5 => array ('channel_config'=>'3/1', 'num_channels'=>4, 'channel_order'=>'L,C,R,S'),
            6 => array ('channel_config'=>'2/2', 'num_channels'=>4, 'channel_order'=>'L,R,SL,SR'),
            7 => array ('channel_config'=>'3/2', 'num_channels'=>5, 'channel_order'=>'L,C,R,SL,SR')
        );
        return (isset($lookup[$acmod]) ? $lookup[$acmod] : false);
    }



    public static function AC3centerMixLevelLookup($cmixlev) {

        static $lookup;
        if (!@$lookup) {
            $lookup = array (
                0 => pow(2, -3.0 / 6), // 0.707 (–3.0 dB)
                1 => pow(2, -4.5 / 6), // 0.595 (–4.5 dB)
                2 => pow(2, -6.0 / 6), // 0.500 (–6.0 dB)
                3 => 'reserved'
            );
        }
        return (isset($lookup[$cmixlev]) ? $lookup[$cmixlev] : false);
    }



    public static function AC3surroundMixLevelLookup($surmixlev) {

        static $lookup;
        if (!@$lookup) {
            $lookup = array (
                0 => pow(2, -3.0 / 6),
                1 => pow(2, -6.0 / 6),
                2 => 0,
                3 => 'reserved'
            );
        }
        return (isset($lookup[$surmixlev]) ? $lookup[$surmixlev] : false);
    }



    public static function AC3dolbySurroundModeLookup($dsurmod) {

        static $lookup = array (
            0 => 'not indicated',
            1 => 'Not Dolby Surround encoded',
            2 => 'Dolby Surround encoded',
            3 => 'reserved'
        );
        return (isset($lookup[$dsurmod]) ? $lookup[$dsurmod] : false);
    }



    public static function AC3channelsEnabledLookup($acmod, $lfeon) {

        return array (
            'ch1'            => $acmod == 0,
            'ch2'            => $acmod == 0,
            'left'           => $acmod > 1,
            'right'          => $acmod > 1,
            'center'         => (bool)($acmod & 0x01),
            'surround_mono'  => $acmod == 4 || $acmod == 5,
            'surround_left'  => $acmod == 6 || $acmod == 7,
            'surround_right' => $acmod == 6 || $acmod == 7,
            'lfe'            => $lfeon
        );
    }



    public static function AC3heavyCompression($compre) {

        // The first four bits indicate gain changes in 6.02dB increments which can be
        // implemented with an arithmetic shift operation. The following four bits
        // indicate linear gain changes, and require a 5-bit multiply.
        // We will represent the two 4-bit fields of compr as follows:
        //   X0 X1 X2 X3 . Y4 Y5 Y6 Y7
        // The meaning of the X values is most simply described by considering X to represent a 4-bit
        // signed integer with values from –8 to +7. The gain indicated by X is then (X + 1) * 6.02 dB. The
        // following table shows this in detail.

        // Meaning of 4 msb of compr
        //  7    +48.16 dB
        //  6    +42.14 dB
        //  5    +36.12 dB
        //  4    +30.10 dB
        //  3    +24.08 dB
        //  2    +18.06 dB
        //  1    +12.04 dB
        //  0     +6.02 dB
        // -1         0 dB
        // -2     –6.02 dB
        // -3    –12.04 dB
        // -4    –18.06 dB
        // -5    –24.08 dB
        // -6    –30.10 dB
        // -7    –36.12 dB
        // -8    –42.14 dB

        $fourbit = str_pad(decbin(($compre & 0xF0) >> 4), 4, '0', STR_PAD_LEFT);
        if ($fourbit{0} == '1') {
            $log_gain = -8 + bindec(substr($fourbit, 1));
        } else {
            $log_gain = bindec(substr($fourbit, 1));
        }
        $log_gain = ($log_gain + 1) * (20 * log10(2));

        // The value of Y is a linear representation of a gain change of up to –6 dB. Y is considered to
        // be an unsigned fractional integer, with a leading value of 1, or: 0.1 Y4 Y5 Y6 Y7 (base 2). Y can
        // represent values between 0.111112 (or 31/32) and 0.100002 (or 1/2). Thus, Y can represent gain
        // changes from –0.28 dB to –6.02 dB.

        $lin_gain = (16 + ($compre & 0x0F)) / 32;

        // The combination of X and Y values allows compr to indicate gain changes from
        //  48.16 – 0.28 = +47.89 dB, to
        // –42.14 – 6.02 = –48.16 dB.

        return $log_gain - $lin_gain;
    }



    public static function AC3roomTypeLookup($roomtyp) {

        static $lookup = array (
            0 => 'not indicated',
            1 => 'large room, X curve monitor',
            2 => 'small room, flat monitor',
            3 => 'reserved'
        );
        return (isset($lookup[$roomtyp]) ? $lookup[$roomtyp] : false);
    }



    public static function AC3frameSizeLookup($frmsizecod, $fscod) {

        $padding     = (bool)($frmsizecod % 2);
        $frame_size_id =   floor($frmsizecod / 2);

        static $lookup = array (
            0  => array (128, 138, 192),
            1  => array (40, 160, 174, 240),
            2  => array (48, 192, 208, 288),
            3  => array (56, 224, 242, 336),
            4  => array (64, 256, 278, 384),
            5  => array (80, 320, 348, 480),
            6  => array (96, 384, 416, 576),
            7  => array (112, 448, 486, 672),
            8  => array (128, 512, 556, 768),
            9  => array (160, 640, 696, 960),
            10 => array (192, 768, 834, 1152),
            11 => array (224, 896, 974, 1344),
            12 => array (256, 1024, 1114, 1536),
            13 => array (320, 1280, 1392, 1920),
            14 => array (384, 1536, 1670, 2304),
            15 => array (448, 1792, 1950, 2688),
            16 => array (512, 2048, 2228, 3072),
            17 => array (576, 2304, 2506, 3456),
            18 => array (640, 2560, 2786, 3840)
        );
        if (($fscod == 1) && $padding) {
            // frame lengths are padded by 1 word (16 bits) at 44100
            $lookup[$frmsizecod] += 2;
        }
        return (isset($lookup[$frame_size_id][$fscod]) ? $lookup[$frame_size_id][$fscod] : false);
    }



    public static function AC3bitrateLookup($frmsizecod) {

        static $lookup = array (
            0  => 32000,
            1  => 40000,
            2  => 48000,
            3  => 56000,
            4  => 64000,
            5  => 80000,
            6  => 96000,
            7  => 112000,
            8  => 128000,
            9  => 160000,
            10 => 192000,
            11 => 224000,
            12 => 256000,
            13 => 320000,
            14 => 384000,
            15 => 448000,
            16 => 512000,
            17 => 576000,
            18 => 640000
        );
        $frame_size_id = floor($frmsizecod / 2);
        return (isset($lookup[$frame_size_id]) ? $lookup[$frame_size_id] : false);
    }

}

?>