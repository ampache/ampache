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
// | module.audio-video.mpeg.php                                          |
// | Module for analyzing MPEG files                                      |
// | dependencies: module.audio.mp3.php                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.mpeg.php,v 1.3 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_mpeg extends getid3_handler
{

    const VIDEO_PICTURE_START   = "\x00\x00\x01\x00";
    const VIDEO_USER_DATA_START = "\x00\x00\x01\xB2";
    const VIDEO_SEQUENCE_HEADER = "\x00\x00\x01\xB3";
    const VIDEO_SEQUENCE_ERROR  = "\x00\x00\x01\xB4";
    const VIDEO_EXTENSION_START = "\x00\x00\x01\xB5";
    const VIDEO_SEQUENCE_END    = "\x00\x00\x01\xB7";
    const VIDEO_GROUP_START     = "\x00\x00\x01\xB8";
    const AUDIO_START           = "\x00\x00\x01\xC0";
    

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->info['mpeg']['video']['raw'] = array ();
        $info_mpeg_video     = &$getid3->info['mpeg']['video'];
        $info_mpeg_video_raw = &$info_mpeg_video['raw'];
        
        $getid3->info['video'] = array ();
        $info_video = &$getid3->info['video'];
        
        $getid3->include_module('audio.mp3');
        
        if ($getid3->info['avdataend'] <= $getid3->info['avdataoffset']) {
            throw new getid3_exception('"avdataend" ('.$getid3->info['avdataend'].') is unexpectedly less-than-or-equal-to "avdataoffset" ('.$getid3->info['avdataoffset'].')');
        }

        $getid3->info['fileformat'] = 'mpeg';
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $mpeg_stream_data        = fread($getid3->fp, min(100000, $getid3->info['avdataend'] - $getid3->info['avdataoffset']));
        $mpeg_stream_data_length = strlen($mpeg_stream_data);

        $video_chunk_offset = 0;
        while (substr($mpeg_stream_data, $video_chunk_offset++, 4) !== getid3_mpeg::VIDEO_SEQUENCE_HEADER) {
            if ($video_chunk_offset >= $mpeg_stream_data_length) {
                throw new getid3_exception('Could not find start of video block in the first 100,000 bytes (or before end of file) - this might not be an MPEG-video file?');
            }
        }

        // Start code                       32 bits
        // horizontal frame size            12 bits
        // vertical frame size              12 bits
        // pixel aspect ratio                4 bits
        // frame rate                        4 bits
        // bitrate                          18 bits
        // marker bit                        1 bit
        // VBV buffer size                  10 bits
        // constrained parameter flag        1 bit
        // intra quant. matrix flag          1 bit
        // intra quant. matrix values      512 bits (present if matrix flag == 1)
        // non-intra quant. matrix flag      1 bit
        // non-intra quant. matrix values  512 bits (present if matrix flag == 1)

        $info_video['dataformat'] = 'mpeg';

        $video_chunk_offset += (strlen(getid3_mpeg::VIDEO_SEQUENCE_HEADER) - 1);

        $frame_size_dword = getid3_lib::BigEndian2Int(substr($mpeg_stream_data, $video_chunk_offset, 3));
        $video_chunk_offset += 3;

        $aspect_ratio_frame_rate_dword = getid3_lib::BigEndian2Int(substr($mpeg_stream_data, $video_chunk_offset, 1));
        $video_chunk_offset += 1;

        $assorted_information = getid3_lib::BigEndian2Bin(substr($mpeg_stream_data, $video_chunk_offset, 4));
        $video_chunk_offset += 4;

        $info_mpeg_video_raw['framesize_horizontal']   = ($frame_size_dword & 0xFFF000) >> 12; // 12 bits for horizontal frame size
        $info_mpeg_video_raw['framesize_vertical']     = ($frame_size_dword & 0x000FFF);       // 12 bits for vertical frame size
        $info_mpeg_video_raw['pixel_aspect_ratio']     = ($aspect_ratio_frame_rate_dword & 0xF0) >> 4;
        $info_mpeg_video_raw['frame_rate']             = ($aspect_ratio_frame_rate_dword & 0x0F);
                                                                        
        $info_mpeg_video['framesize_horizontal']          = $info_mpeg_video_raw['framesize_horizontal'];
        $info_mpeg_video['framesize_vertical']            = $info_mpeg_video_raw['framesize_vertical'];

        $info_mpeg_video['pixel_aspect_ratio']            = $this->MPEGvideoAspectRatioLookup($info_mpeg_video_raw['pixel_aspect_ratio']);
        $info_mpeg_video['pixel_aspect_ratio_text']       = $this->MPEGvideoAspectRatioTextLookup($info_mpeg_video_raw['pixel_aspect_ratio']);
        $info_mpeg_video['frame_rate']                    = $this->MPEGvideoFramerateLookup($info_mpeg_video_raw['frame_rate']);

        $info_mpeg_video_raw['bitrate']                =       bindec(substr($assorted_information,  0, 18));
        $info_mpeg_video_raw['marker_bit']             = (bool)bindec($assorted_information{18});
        $info_mpeg_video_raw['vbv_buffer_size']        =       bindec(substr($assorted_information, 19, 10));
        $info_mpeg_video_raw['constrained_param_flag'] = (bool)bindec($assorted_information{29});
        $info_mpeg_video_raw['intra_quant_flag']       = (bool)bindec($assorted_information{30});
        
        if ($info_mpeg_video_raw['intra_quant_flag']) {

            // read 512 bits
            $info_mpeg_video_raw['intra_quant']          = getid3_lib::BigEndian2Bin(substr($mpeg_stream_data, $video_chunk_offset, 64));
            $video_chunk_offset += 64;

            $info_mpeg_video_raw['non_intra_quant_flag'] = (bool)bindec($info_mpeg_video_raw['intra_quant']{511});
            $info_mpeg_video_raw['intra_quant']          =       bindec($assorted_information{31}).substr(getid3_lib::BigEndian2Bin(substr($mpeg_stream_data, $video_chunk_offset, 64)), 0, 511);

            if ($info_mpeg_video_raw['non_intra_quant_flag']) {
                $info_mpeg_video_raw['non_intra_quant'] = substr($mpeg_stream_data, $video_chunk_offset, 64);
                $video_chunk_offset += 64;
            }

        } else {

            $info_mpeg_video_raw['non_intra_quant_flag'] = (bool)bindec($assorted_information{31});
            if ($info_mpeg_video_raw['non_intra_quant_flag']) {
                $info_mpeg_video_raw['non_intra_quant'] = substr($mpeg_stream_data, $video_chunk_offset, 64);
                $video_chunk_offset += 64;
            }
        }

        if ($info_mpeg_video_raw['bitrate'] == 0x3FFFF) { // 18 set bits

            $getid3->warning('This version of getID3() cannot determine average bitrate of VBR MPEG video files');
            $info_mpeg_video['bitrate_mode'] = 'vbr';

        } else {

            $info_mpeg_video['bitrate']      = $info_mpeg_video_raw['bitrate'] * 400;
            $info_mpeg_video['bitrate_mode'] = 'cbr';
            $info_video['bitrate']              = $info_mpeg_video['bitrate'];
        }

        $info_video['resolution_x']       = $info_mpeg_video['framesize_horizontal'];
        $info_video['resolution_y']       = $info_mpeg_video['framesize_vertical'];
        $info_video['frame_rate']         = $info_mpeg_video['frame_rate'];
        $info_video['bitrate_mode']       = $info_mpeg_video['bitrate_mode'];
        $info_video['pixel_aspect_ratio'] = $info_mpeg_video['pixel_aspect_ratio'];
        $info_video['lossless']           = false;
        $info_video['bits_per_sample']    = 24;


        //0x000001B3 begins the sequence_header of every MPEG video stream.
        //But in MPEG-2, this header must immediately be followed by an
        //extension_start_code (0x000001B5) with a sequence_extension ID (1).
        //(This extension contains all the additional MPEG-2 stuff.)
        //MPEG-1 doesn't have this extension, so that's a sure way to tell the
        //difference between MPEG-1 and MPEG-2 video streams.

        $info_video['codec'] = substr($mpeg_stream_data, $video_chunk_offset, 4) == getid3_mpeg::VIDEO_EXTENSION_START ? 'MPEG-2' : 'MPEG-1';

        $audio_chunk_offset = 0;
        while (true) {
            while (substr($mpeg_stream_data, $audio_chunk_offset++, 4) !== getid3_mpeg::AUDIO_START) {
                if ($audio_chunk_offset >= $mpeg_stream_data_length) {
                    break 2;
                }
            }

            for ($i = 0; $i <= 7; $i++) {
                // some files have the MPEG-audio header 8 bytes after the end of the $00 $00 $01 $C0 signature, some have it up to 13 bytes (or more?) after
                // I have no idea why or what the difference is, so this is a stupid hack.
                // If anybody has any better idea of what's going on, please let me know - info@getid3.org

                // make copy of info
                $dummy = $getid3->info;

                // clone getid3 - better safe than sorry
                $clone = clone $this->getid3;
                
                // check
                $mp3 = new getid3_mp3($clone);
                if ($mp3->decodeMPEGaudioHeader($getid3->fp, ($audio_chunk_offset + 3) + 8 + $i, $dummy, false)) {

                    $getid3->info = $dummy;
                    $getid3->info['audio']['bitrate_mode'] = 'cbr';
                    $getid3->info['audio']['lossless']     = false;
                    break 2;
                }
                
                // destroy copy
                unset($dummy);
            }
        }

        // Temporary hack to account for interleaving overhead:
        if (!empty($info_video['bitrate']) && !empty($getid3->info['audio']['bitrate'])) {
            $getid3->info['playtime_seconds'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / ($info_video['bitrate'] + $getid3->info['audio']['bitrate']);

            // Interleaved MPEG audio/video files have a certain amount of overhead that varies
            // by both video and audio bitrates, and not in any sensible, linear/logarithmic patter
            // Use interpolated lookup tables to approximately guess how much is overhead, because
            // playtime is calculated as filesize / total-bitrate
            $getid3->info['playtime_seconds'] *= $this->MPEGsystemNonOverheadPercentage($info_video['bitrate'], $getid3->info['audio']['bitrate']);

            //switch ($info_video['bitrate']) {
            //    case('5000000'):
            //        $multiplier = 0.93292642112380355828048824319889;
            //        break;
            //    case('5500000'):
            //        $multiplier = 0.93582895375200989965359777343219;
            //        break;
            //    case('6000000'):
            //        $multiplier = 0.93796247714820932532911373859139;
            //        break;
            //    case('7000000'):
            //        $multiplier = 0.9413264083635103463010117778776;
            //        break;
            //    default:
            //        $multiplier = 1;
            //        break;
            //}
            //$getid3->info['playtime_seconds'] *= $multiplier;
            //$getid3->warning('Interleaved MPEG audio/video playtime may be inaccurate. With current hack should be within a few seconds of accurate. Report to info@getid3.org if off by more than 10 seconds.');
        
            if ($info_video['bitrate'] < 50000) {
                $getid3->warning('Interleaved MPEG audio/video playtime may be slightly inaccurate for video bitrates below 100kbps. Except in extreme low-bitrate situations, error should be less than 1%. Report to info@getid3.org if greater than this.');
            }
        }

        return true;
    }



    public static function MPEGsystemNonOverheadPercentage($video_bitrate, $audio_bitrate) {
        
        $overhead_percentage = 0;

        $audio_bitrate = max(min($audio_bitrate / 1000,   384), 32); // limit to range of 32kbps - 384kbps (should be only legal bitrates, but maybe VBR?)
        $video_bitrate = max(min($video_bitrate / 1000, 10000), 10); // limit to range of 10kbps -  10Mbps (beyond that curves flatten anyways, no big loss)

        //OMBB[audiobitrate]                 = array (   video-10kbps,       video-100kbps,      video-1000kbps,     video-10000kbps)
        static $overhead_multiplier_by_bitrate = array (
             32 => array (0, 0.9676287944368530, 0.9802276264360310, 0.9844916183244460, 0.9852821845179940),
             48 => array (0, 0.9779100089209830, 0.9787770035359320, 0.9846738664076130, 0.9852683013799960),
             56 => array (0, 0.9731249855367600, 0.9776624308938040, 0.9832606361852130, 0.9843922606633340),
             64 => array (0, 0.9755642683275760, 0.9795256705493390, 0.9836573009193170, 0.9851122539404470),
             96 => array (0, 0.9788025247497290, 0.9798553314148700, 0.9822956869792560, 0.9834815119124690),
            128 => array (0, 0.9816940050925480, 0.9821675936072120, 0.9829756927470870, 0.9839763420152050),
            160 => array (0, 0.9825894094561180, 0.9820913399073960, 0.9823907143253970, 0.9832821783651570),
            192 => array (0, 0.9832038474336260, 0.9825731694317960, 0.9821028622712400, 0.9828262076447620),
            224 => array (0, 0.9836516298538770, 0.9824718601823890, 0.9818302180625380, 0.9823735101626480),
            256 => array (0, 0.9845863022094920, 0.9837229411967540, 0.9824521662210830, 0.9828645172100790),
            320 => array (0, 0.9849565280263180, 0.9837683142805110, 0.9822885275960400, 0.9824424382727190),
            384 => array (0, 0.9856094774357600, 0.9844573394432720, 0.9825970399837330, 0.9824673808303890)
        );

        $bitrate_to_use_min = $bitrate_to_use_max = $previous_bitrate = 32;

        foreach ($overhead_multiplier_by_bitrate as $key => $value) {
        
            if ($audio_bitrate >= $previous_bitrate) {
                $bitrate_to_use_min = $previous_bitrate;
            }
            if ($audio_bitrate < $key) {
                $bitrate_to_use_max = $key;
                break;
            }
            $previous_bitrate = $key;
        }
        
        $factor_a = ($bitrate_to_use_max - $audio_bitrate) / ($bitrate_to_use_max - $bitrate_to_use_min);

        $video_bitrate_log10 = log10($video_bitrate);
        $video_factor_min1   = $overhead_multiplier_by_bitrate[$bitrate_to_use_min][floor($video_bitrate_log10)];
        $video_factor_min2   = $overhead_multiplier_by_bitrate[$bitrate_to_use_max][floor($video_bitrate_log10)];
        $video_factor_max1   = $overhead_multiplier_by_bitrate[$bitrate_to_use_min][ceil($video_bitrate_log10)];
        $video_factor_max2   = $overhead_multiplier_by_bitrate[$bitrate_to_use_max][ceil($video_bitrate_log10)];
        $factor_v = $video_bitrate_log10 - floor($video_bitrate_log10);

        $overhead_percentage  = $video_factor_min1 *      $factor_a  *      $factor_v;
        $overhead_percentage += $video_factor_min2 * (1 - $factor_a) *      $factor_v;
        $overhead_percentage += $video_factor_max1 *      $factor_a  * (1 - $factor_v);
        $overhead_percentage += $video_factor_max2 * (1 - $factor_a) * (1 - $factor_v);

        return $overhead_percentage;
    }



    public static function MPEGvideoFramerateLookup($raw_frame_rate) {
        
        $lookup = array (0, 23.976, 24, 25, 29.97, 30, 50, 59.94, 60);
        
        return (float)(isset($lookup[$raw_frame_rate]) ? $lookup[$raw_frame_rate] : 0);
    }



    public static function MPEGvideoAspectRatioLookup($raw_aspect_ratio) {
        
        $lookup = array (0, 1, 0.6735, 0.7031, 0.7615, 0.8055, 0.8437, 0.8935, 0.9157, 0.9815, 1.0255, 1.0695, 1.0950, 1.1575, 1.2015, 0);
        
        return (float)(isset($lookup[$raw_aspect_ratio]) ? $lookup[$raw_aspect_ratio] : 0);
    }



    public static function MPEGvideoAspectRatioTextLookup($raw_aspect_ratio) {
        
        $lookup = array ('forbidden', 'square pixels', '0.6735', '16:9, 625 line, PAL', '0.7615', '0.8055', '16:9, 525 line, NTSC', '0.8935', '4:3, 625 line, PAL, CCIR601', '0.9815', '1.0255', '1.0695', '4:3, 525 line, NTSC, CCIR601', '1.1575', '1.2015', 'reserved');
        
        return (isset($lookup[$raw_aspect_ratio]) ? $lookup[$raw_aspect_ratio] : '');
    }

}


?>