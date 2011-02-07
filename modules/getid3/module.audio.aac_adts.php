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
// | module.audio.aac_adts.php                                            |
// | Module for analyzing AAC files with ADTS header.                     |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.aac_adts.php,v 1.4 2006/11/02 10:48:01 ah Exp $



class getid3_aac_adts extends getid3_handler
{

    public $option_max_frames_to_scan   = 1000000;
    public $option_return_extended_info = false;


    private $decbin_cache;
    private $bitrate_cache;



    public function __construct(getID3 $getid3) {

        parent::__construct($getid3);

        // Populate bindec_cache
        for ($i = 0; $i < 256; $i++) {
                $this->decbin_cache[chr($i)] = str_pad(decbin($i), 8, '0', STR_PAD_LEFT);
        }

        // Init cache
        $this->bitrate_cache = array ();

        // Fast scanning?
        if (!$getid3->option_accurate_results) {
            $this->option_max_frames_to_scan = 200;
            $getid3->warning('option_accurate_results set to false - bitrate and playing time are not accurate.');
        }
    }



    public function Analyze() {

        $getid3 = $this->getid3;

        // based loosely on code from AACfile by Jurgen Faul  <jfaulØgmx.de>
        // http://jfaul.de/atl  or  http://j-faul.virtualave.net/atl/atl.html


        // http://faac.sourceforge.net/wiki/index.php?page=ADTS

        // * ADTS Fixed Header: these don't change from frame to frame
        // syncword                                       12    always: '111111111111'
        // ID                                              1    0: MPEG-4, 1: MPEG-2
        // layer                                           2    always: '00'
        // protection_absent                               1
        // profile                                         2
        // sampling_frequency_index                        4
        // private_bit                                     1
        // channel_configuration                           3
        // original/copy                                   1
        // home                                            1
        // emphasis                                        2    only if ID == 0 (ie MPEG-4)

        // * ADTS Variable Header: these can change from frame to frame
        // copyright_identification_bit                    1
        // copyright_identification_start                  1
        // aac_frame_length                               13    length of the frame including header (in bytes)
        // adts_buffer_fullness                           11    0x7FF indicates VBR
        // no_raw_data_blocks_in_frame                     2

        // * ADTS Error check
        // crc_check                                      16    only if protection_absent == 0

        $getid3->info['aac']['header'] = array () ;
        $info_aac        = &$getid3->info['aac'];
        $info_aac_header = & $info_aac['header'];

        $byte_offset  = $frame_number = 0;

        while (true) {

            // Breaks out when end-of-file encountered, or invalid data found,
            // or MaxFramesToScan frames have been scanned

            fseek($getid3->fp, $byte_offset, SEEK_SET);

            // First get substring
            $sub_string = fread($getid3->fp, 10);
            $sub_string_length = strlen($sub_string);
            if ($sub_string_length != 10) {
                throw new getid3_exception('Failed to read 10 bytes at offset '.(ftell($getid3->fp) - $sub_string_length).' (only read '.$sub_string_length.' bytes)');
            }

            // Initialise $aac_header_bitstream
            $aac_header_bitstream = '';

            // Loop thru substring chars
            for ($i = 0; $i < 10; $i++) {
                $aac_header_bitstream .= $this->decbin_cache[$sub_string[$i]];
            }

            $sync_test  = bindec(substr($aac_header_bitstream, 0, 12));
            $bit_offset = 12;

            if ($sync_test != 0x0FFF) {
                throw new getid3_exception('Synch pattern (0x0FFF) not found at offset '.(ftell($getid3->fp) - 10).' (found 0x0'.strtoupper(dechex($sync_test)).' instead)');
            }

            // Only gather info once - this takes time to do 1000 times!
            if ($frame_number > 0) {

                // MPEG-4: 20,  // MPEG-2: 18
                $bit_offset += $aac_header_bitstream[$bit_offset] ? 18 : 20;
            }

            // Gather info for first frame only - this takes time to do 1000 times!
            else {

                $info_aac['header_type']             = 'ADTS';
                $info_aac_header['synch']            = $sync_test;
                $getid3->info['fileformat']          = 'aac';
                $getid3->info['audio']['dataformat'] = 'aac';

                $info_aac_header['mpeg_version']     = $aac_header_bitstream{$bit_offset++} == '0' ? 4 : 2;
                $info_aac_header['layer']            = bindec(substr($aac_header_bitstream, $bit_offset, 2));
                $bit_offset += 2;

                if ($info_aac_header['layer'] != 0) {
                    throw new getid3_exception('Layer error - expected 0x00, found 0x'.dechex($info_aac_header['layer']).' instead');
                }

                $info_aac_header['crc_present'] = $aac_header_bitstream{$bit_offset++} == '0' ? true : false;

                $info_aac_header['profile_id'] = bindec(substr($aac_header_bitstream, $bit_offset, 2));
                $bit_offset += 2;

                $info_aac_header['profile_text'] = getid3_aac_adts::AACprofileLookup($info_aac_header['profile_id'], $info_aac_header['mpeg_version']);

                $info_aac_header['sample_frequency_index'] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;

                $info_aac_header['sample_frequency'] = getid3_aac_adts::AACsampleRateLookup($info_aac_header['sample_frequency_index']);

                $getid3->info['audio']['sample_rate'] = $info_aac_header['sample_frequency'];

                $info_aac_header['private'] = $aac_header_bitstream{$bit_offset++} == 1;

                $info_aac_header['channel_configuration'] = $getid3->info['audio']['channels'] = bindec(substr($aac_header_bitstream, $bit_offset, 3));
                $bit_offset += 3;

                $info_aac_header['original'] = $aac_header_bitstream{$bit_offset++} == 1;
                $info_aac_header['home']     = $aac_header_bitstream{$bit_offset++} == 1;

                if ($info_aac_header['mpeg_version'] == 4) {
                    $info_aac_header['emphasis']  = bindec(substr($aac_header_bitstream, $bit_offset, 2));
                    $bit_offset += 2;
                }

                if ($this->option_return_extended_info) {

                    $info_aac[$frame_number]['copyright_id_bit']   = $aac_header_bitstream{$bit_offset++} == 1;
                    $info_aac[$frame_number]['copyright_id_start'] = $aac_header_bitstream{$bit_offset++} == 1;

                }  else {
                    $bit_offset += 2;
                }
            }

            $frame_length = bindec(substr($aac_header_bitstream, $bit_offset, 13));

            if (!isset($this->bitrate_cache[$frame_length])) {
                $this->bitrate_cache[$frame_length] = ($info_aac_header['sample_frequency'] / 1024) * $frame_length * 8;
            }
            @$info_aac['bitrate_distribution'][$this->bitrate_cache[$frame_length]]++;

            $info_aac[$frame_number]['aac_frame_length']     = $frame_length;
            $bit_offset += 13;

            $info_aac[$frame_number]['adts_buffer_fullness'] = bindec(substr($aac_header_bitstream, $bit_offset, 11));
            $bit_offset += 11;

            $getid3->info['audio']['bitrate_mode'] = ($info_aac[$frame_number]['adts_buffer_fullness'] == 0x07FF) ? 'vbr' : 'cbr';

            $info_aac[$frame_number]['num_raw_data_blocks']  = bindec(substr($aac_header_bitstream, $bit_offset, 2));
            $bit_offset += 2;

            if ($info_aac_header['crc_present']) {
                $bit_offset += 16;
            }

            if (!$this->option_return_extended_info) {
                unset($info_aac[$frame_number]);
            }

            $byte_offset += $frame_length;
            if ((++$frame_number < $this->option_max_frames_to_scan) && (($byte_offset + 10) < $getid3->info['avdataend'])) {

                // keep scanning

            } else {

                $info_aac['frames']    = $frame_number;
                $getid3->info['playtime_seconds'] = ($getid3->info['avdataend'] / $byte_offset) * (($frame_number * 1024) / $info_aac_header['sample_frequency']);  // (1 / % of file scanned) * (samples / (samples/sec)) = seconds
                $getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
                ksort($info_aac['bitrate_distribution']);

                $getid3->info['audio']['encoder_options'] = $info_aac['header_type'].' '.$info_aac_header['profile_text'];

                return true;
            }
        }
    }



    public static function AACsampleRateLookup($samplerate_id) {

        static $lookup = array (
            0  => 96000,
            1  => 88200,
            2  => 64000,
            3  => 48000,
            4  => 44100,
            5  => 32000,
            6  => 24000,
            7  => 22050,
            8  => 16000,
            9  => 12000,
            10 => 11025,
            11 => 8000,
            12 => 0,
            13 => 0,
            14 => 0,
            15 => 0
        );
        return (isset($lookup[$samplerate_id]) ? $lookup[$samplerate_id] : 'invalid');
    }



    public static function AACprofileLookup($profile_id, $mpeg_version) {

        static $lookup = array (
            2 => array (
                0 => 'Main profile',
                1 => 'Low Complexity profile (LC)',
                2 => 'Scalable Sample Rate profile (SSR)',
                3 => '(reserved)'
            ),
            4 => array (
                0 => 'AAC_MAIN',
                1 => 'AAC_LC',
                2 => 'AAC_SSR',
                3 => 'AAC_LTP'
            )
        );
        return (isset($lookup[$mpeg_version][$profile_id]) ? $lookup[$mpeg_version][$profile_id] : 'invalid');
    }


}


?>