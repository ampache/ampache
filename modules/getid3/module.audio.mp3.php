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
// | module.audio.mp3.php                                                 |
// | Module for analyzing MPEG Audio Layer 1,2,3 files.                   |
// | dependencies: none                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.mp3.php,v 1.10 2006/11/16 22:57:57 ah Exp $



class getid3_mp3 extends getid3_handler
{
    // Number of frames to scan to determine if MPEG-audio sequence is valid.
    // Lower this number to 5-20 for faster scanning
    // Increase this number to 50+ for most accurate detection of valid VBR/CBR mpeg-audio streams
    const VALID_CHECK_FRAMES = 35;
    

    public function Analyze() {

        $this->getAllMPEGInfo($this->getid3->fp, $this->getid3->info);

        return true;   
    }
    
    
    public function AnalyzeMPEGaudioInfo() {
        
        $this->getOnlyMPEGaudioInfo($this->getid3->fp, $this->getid3->info, $this->getid3->info['avdataoffset'], false);
    }


    public function getAllMPEGInfo(&$fd, &$info) {

        $this->getOnlyMPEGaudioInfo($fd, $info, 0 + $info['avdataoffset']);

        if (isset($info['mpeg']['audio']['bitrate_mode'])) {
            $info['audio']['bitrate_mode'] = strtolower($info['mpeg']['audio']['bitrate_mode']);
        }

        if (((isset($info['id3v2']['headerlength']) && ($info['avdataoffset'] > $info['id3v2']['headerlength'])) || (!isset($info['id3v2']) && ($info['avdataoffset'] > 0)))) {

            $synch_offset_warning = 'Unknown data before synch ';
            if (isset($info['id3v2']['headerlength'])) {
                $synch_offset_warning .= '(ID3v2 header ends at '.$info['id3v2']['headerlength'].', then '.($info['avdataoffset'] - $info['id3v2']['headerlength']).' bytes garbage, ';
            } else {
                $synch_offset_warning .= '(should be at beginning of file, ';
            }
            $synch_offset_warning .= 'synch detected at '.$info['avdataoffset'].')';
            if ($info['audio']['bitrate_mode'] == 'cbr') {

                if (!empty($info['id3v2']['headerlength']) && (($info['avdataoffset'] - $info['id3v2']['headerlength']) == $info['mpeg']['audio']['framelength'])) {

                    $synch_offset_warning .= '. This is a known problem with some versions of LAME (3.90-3.92) DLL in CBR mode.';
                    $info['audio']['codec'] = 'LAME';
                    $current_data_lame_version_string = 'LAME3.';

                } elseif (empty($info['id3v2']['headerlength']) && ($info['avdataoffset'] == $info['mpeg']['audio']['framelength'])) {

                    $synch_offset_warning .= '. This is a known problem with some versions of LAME (3.90 - 3.92) DLL in CBR mode.';
                    $info['audio']['codec'] = 'LAME';
                    $current_data_lame_version_string = 'LAME3.';

                }

            }
            $this->getid3->warning($synch_offset_warning);

        }

        if (isset($info['mpeg']['audio']['LAME'])) {
            $info['audio']['codec'] = 'LAME';
            if (!empty($info['mpeg']['audio']['LAME']['long_version'])) {
                $info['audio']['encoder'] = rtrim($info['mpeg']['audio']['LAME']['long_version'], "\x00");
            } elseif (!empty($info['mpeg']['audio']['LAME']['short_version'])) {
                $info['audio']['encoder'] = rtrim($info['mpeg']['audio']['LAME']['short_version'], "\x00");
            }
        }

        $current_data_lame_version_string = (!empty($current_data_lame_version_string) ? $current_data_lame_version_string : @$info['audio']['encoder']);
        if (!empty($current_data_lame_version_string) && (substr($current_data_lame_version_string, 0, 6) == 'LAME3.') && !preg_match('[0-9\)]', substr($current_data_lame_version_string, -1))) {
            // a version number of LAME that does not end with a number like "LAME3.92"
            // or with a closing parenthesis like "LAME3.88 (alpha)"
            // or a version of LAME with the LAMEtag-not-filled-in-DLL-mode bug (3.90-3.92)

            // not sure what the actual last frame length will be, but will be less than or equal to 1441
            $possibly_longer_lame_version_frame_length = 1441;

            // Not sure what version of LAME this is - look in padding of last frame for longer version string
            $possible_lame_version_string_offset = $info['avdataend'] - $possibly_longer_lame_version_frame_length;
            fseek($fd, $possible_lame_version_string_offset);
            $possibly_longer_lame_version_data = fread($fd, $possibly_longer_lame_version_frame_length);
            switch (substr($current_data_lame_version_string, -1)) {
                case 'a':
                case 'b':
                    // "LAME3.94a" will have a longer version string of "LAME3.94 (alpha)" for example
                    // need to trim off "a" to match longer string
                    $current_data_lame_version_string = substr($current_data_lame_version_string, 0, -1);
                    break;
            }
            if (($possibly_longer_lame_version_string = strstr($possibly_longer_lame_version_data, $current_data_lame_version_string)) !== false) {
                if (substr($possibly_longer_lame_version_string, 0, strlen($current_data_lame_version_string)) == $current_data_lame_version_string) {
                    $possibly_longer_lame_version_new_string = substr($possibly_longer_lame_version_string, 0, strspn($possibly_longer_lame_version_string, 'LAME0123456789., (abcdefghijklmnopqrstuvwxyzJFSOND)')); //"LAME3.90.3"  "LAME3.87 (beta 1, Sep 27 2000)" "LAME3.88 (beta)"
                    if (strlen($possibly_longer_lame_version_new_string) > strlen(@$info['audio']['encoder'])) {
                        $info['audio']['encoder'] = $possibly_longer_lame_version_new_string;
                    }
                }
            }
        }
        if (!empty($info['audio']['encoder'])) {
            $info['audio']['encoder'] = rtrim($info['audio']['encoder'], "\x00 ");
        }

        switch (@$info['mpeg']['audio']['layer']) {
            case 1:
            case 2:
                $info['audio']['dataformat'] = 'mp'.$info['mpeg']['audio']['layer'];
                break;
        }
        if (@$info['fileformat'] == 'mp3') {
            switch ($info['audio']['dataformat']) {
                case 'mp1':
                case 'mp2':
                case 'mp3':
                    $info['fileformat'] = $info['audio']['dataformat'];
                    break;

                default:
                    $this->getid3->warning('Expecting [audio][dataformat] to be mp1/mp2/mp3 when fileformat == mp3, [audio][dataformat] actually "'.$info['audio']['dataformat'].'"');
                    break;
            }
        }
        
        $info['mime_type']         = 'audio/mpeg';
        $info['audio']['lossless'] = false;

        // Calculate playtime
        if (!isset($info['playtime_seconds']) && isset($info['audio']['bitrate']) && ($info['audio']['bitrate'] > 0)) {
            $info['playtime_seconds'] = ($info['avdataend'] - $info['avdataoffset']) * 8 / $info['audio']['bitrate'];
        }

        $info['audio']['encoder_options'] = getid3_mp3::GuessEncoderOptions($info);

        return true;
    }



    public static function GuessEncoderOptions(&$info) {
        // shortcuts
        if (!empty($info['mpeg']['audio'])) {
            $thisfile_mpeg_audio = &$info['mpeg']['audio'];
            if (!empty($thisfile_mpeg_audio['LAME'])) {
                $thisfile_mpeg_audio_lame = &$thisfile_mpeg_audio['LAME'];
            }
        }

        $encoder_options = '';
        static $named_preset_bitrates = array (16, 24, 40, 56, 112, 128, 160, 192, 256);

        if ((@$thisfile_mpeg_audio['VBR_method'] == 'Fraunhofer') && !empty($thisfile_mpeg_audio['VBR_quality'])) {

            $encoder_options = 'VBR q'.$thisfile_mpeg_audio['VBR_quality'];

        } elseif (!empty($thisfile_mpeg_audio_lame['preset_used']) && (!in_array($thisfile_mpeg_audio_lame['preset_used_id'], $named_preset_bitrates))) {

            $encoder_options = $thisfile_mpeg_audio_lame['preset_used'];

        } elseif (!empty($thisfile_mpeg_audio_lame['vbr_quality'])) {

            static $known_encoder_values = array ();
            if (empty($known_encoder_values)) {

                //$known_encoder_values[abrbitrate_minbitrate][vbr_quality][raw_vbr_method][raw_noise_shaping][raw_stereo_mode][ath_type][lowpass_frequency] = 'preset name';
                $known_encoder_values[0xFF][58][1][1][3][2][20500] = '--alt-preset insane';        // 3.90,   3.90.1, 3.92
                $known_encoder_values[0xFF][58][1][1][3][2][20600] = '--alt-preset insane';        // 3.90.2, 3.90.3, 3.91
                $known_encoder_values[0xFF][57][1][1][3][4][20500] = '--alt-preset insane';        // 3.94,   3.95
                $known_encoder_values['**'][78][3][2][3][2][19500] = '--alt-preset extreme';       // 3.90,   3.90.1, 3.92
                $known_encoder_values['**'][78][3][2][3][2][19600] = '--alt-preset extreme';       // 3.90.2, 3.91
                $known_encoder_values['**'][78][3][1][3][2][19600] = '--alt-preset extreme';       // 3.90.3
                $known_encoder_values['**'][78][4][2][3][2][19500] = '--alt-preset fast extreme';  // 3.90,   3.90.1, 3.92
                $known_encoder_values['**'][78][4][2][3][2][19600] = '--alt-preset fast extreme';  // 3.90.2, 3.90.3, 3.91
                $known_encoder_values['**'][78][3][2][3][4][19000] = '--alt-preset standard';      // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
                $known_encoder_values['**'][78][3][1][3][4][19000] = '--alt-preset standard';      // 3.90.3
                $known_encoder_values['**'][78][4][2][3][4][19000] = '--alt-preset fast standard'; // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
                $known_encoder_values['**'][78][4][1][3][4][19000] = '--alt-preset fast standard'; // 3.90.3
                $known_encoder_values['**'][88][4][1][3][3][19500] = '--r3mix';                    // 3.90,   3.90.1, 3.92
                $known_encoder_values['**'][88][4][1][3][3][19600] = '--r3mix';                    // 3.90.2, 3.90.3, 3.91
                $known_encoder_values['**'][67][4][1][3][4][18000] = '--r3mix';                    // 3.94,   3.95
                $known_encoder_values['**'][68][3][2][3][4][18000] = '--alt-preset medium';        // 3.90.3
                $known_encoder_values['**'][68][4][2][3][4][18000] = '--alt-preset fast medium';   // 3.90.3

                $known_encoder_values[0xFF][99][1][1][1][2][0]     = '--preset studio';            // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
                $known_encoder_values[0xFF][58][2][1][3][2][20600] = '--preset studio';            // 3.90.3, 3.93.1
                $known_encoder_values[0xFF][58][2][1][3][2][20500] = '--preset studio';            // 3.93
                $known_encoder_values[0xFF][57][2][1][3][4][20500] = '--preset studio';            // 3.94,   3.95
                $known_encoder_values[0xC0][88][1][1][1][2][0]     = '--preset cd';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
                $known_encoder_values[0xC0][58][2][2][3][2][19600] = '--preset cd';                // 3.90.3, 3.93.1
                $known_encoder_values[0xC0][58][2][2][3][2][19500] = '--preset cd';                // 3.93
                $known_encoder_values[0xC0][57][2][1][3][4][19500] = '--preset cd';                // 3.94,   3.95
                $known_encoder_values[0xA0][78][1][1][3][2][18000] = '--preset hifi';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
                $known_encoder_values[0xA0][58][2][2][3][2][18000] = '--preset hifi';              // 3.90.3, 3.93,   3.93.1
                $known_encoder_values[0xA0][57][2][1][3][4][18000] = '--preset hifi';              // 3.94,   3.95
                $known_encoder_values[0x80][67][1][1][3][2][18000] = '--preset tape';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
                $known_encoder_values[0x80][67][1][1][3][2][15000] = '--preset radio';             // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
                $known_encoder_values[0x70][67][1][1][3][2][15000] = '--preset fm';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
                $known_encoder_values[0x70][58][2][2][3][2][16000] = '--preset tape/radio/fm';     // 3.90.3, 3.93,   3.93.1
                $known_encoder_values[0x70][57][2][1][3][4][16000] = '--preset tape/radio/fm';     // 3.94,   3.95
                $known_encoder_values[0x38][58][2][2][0][2][10000] = '--preset voice';             // 3.90.3, 3.93,   3.93.1
                $known_encoder_values[0x38][57][2][1][0][4][15000] = '--preset voice';             // 3.94,   3.95
                $known_encoder_values[0x38][57][2][1][0][4][16000] = '--preset voice';             // 3.94a14
                $known_encoder_values[0x28][65][1][1][0][2][7500]  = '--preset mw-us';             // 3.90,   3.90.1, 3.92
                $known_encoder_values[0x28][65][1][1][0][2][7600]  = '--preset mw-us';             // 3.90.2, 3.91
                $known_encoder_values[0x28][58][2][2][0][2][7000]  = '--preset mw-us';             // 3.90.3, 3.93,   3.93.1
                $known_encoder_values[0x28][57][2][1][0][4][10500] = '--preset mw-us';             // 3.94,   3.95
                $known_encoder_values[0x28][57][2][1][0][4][11200] = '--preset mw-us';             // 3.94a14
                $known_encoder_values[0x28][57][2][1][0][4][8800]  = '--preset mw-us';             // 3.94a15
                $known_encoder_values[0x18][58][2][2][0][2][4000]  = '--preset phon+/lw/mw-eu/sw'; // 3.90.3, 3.93.1
                $known_encoder_values[0x18][58][2][2][0][2][3900]  = '--preset phon+/lw/mw-eu/sw'; // 3.93
                $known_encoder_values[0x18][57][2][1][0][4][5900]  = '--preset phon+/lw/mw-eu/sw'; // 3.94,   3.95
                $known_encoder_values[0x18][57][2][1][0][4][6200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a14
                $known_encoder_values[0x18][57][2][1][0][4][3200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a15
                $known_encoder_values[0x10][58][2][2][0][2][3800]  = '--preset phone';             // 3.90.3, 3.93.1
                $known_encoder_values[0x10][58][2][2][0][2][3700]  = '--preset phone';             // 3.93
                $known_encoder_values[0x10][57][2][1][0][4][5600]  = '--preset phone';             // 3.94,   3.95
            }

            if (isset($known_encoder_values[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

                $encoder_options = $known_encoder_values[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

            } elseif (isset($known_encoder_values['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

                $encoder_options = $known_encoder_values['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

            } elseif ($info['audio']['bitrate_mode'] == 'vbr') {

                // http://gabriel.mp3-tech.org/mp3infotag.html
                // int    Quality = (100 - 10 * gfp->VBR_q - gfp->quality)h


                $lame_v_value = 10 - ceil($thisfile_mpeg_audio_lame['vbr_quality'] / 10);
                $lame_q_value = 100 - $thisfile_mpeg_audio_lame['vbr_quality'] - ($lame_v_value * 10);
                $encoder_options = '-V'.$lame_v_value.' -q'.$lame_q_value;

            } elseif ($info['audio']['bitrate_mode'] == 'cbr') {

                $encoder_options = strtoupper($info['audio']['bitrate_mode']).ceil($info['audio']['bitrate'] / 1000);

            } else {

                $encoder_options = strtoupper($info['audio']['bitrate_mode']);

            }

        } elseif (!empty($thisfile_mpeg_audio_lame['bitrate_abr'])) {

            $encoder_options = 'ABR'.$thisfile_mpeg_audio_lame['bitrate_abr'];

        } elseif (!empty($info['audio']['bitrate'])) {

            if ($info['audio']['bitrate_mode'] == 'cbr') {
                $encoder_options = strtoupper($info['audio']['bitrate_mode']).ceil($info['audio']['bitrate'] / 1000);
            } else {
                $encoder_options = strtoupper($info['audio']['bitrate_mode']);
            }

        }
        if (!empty($thisfile_mpeg_audio_lame['bitrate_min'])) {
            $encoder_options .= ' -b'.$thisfile_mpeg_audio_lame['bitrate_min'];
        }

        if (@$thisfile_mpeg_audio_lame['encoding_flags']['nogap_prev'] || @$thisfile_mpeg_audio_lame['encoding_flags']['nogap_next']) {
            $encoder_options .= ' --nogap';
        }

        if (!empty($thisfile_mpeg_audio_lame['lowpass_frequency'])) {
            $exploded_options = explode(' ', $encoder_options, 4);
            if ($exploded_options[0] == '--r3mix') {
                $exploded_options[1] = 'r3mix';
            }
            switch ($exploded_options[0]) {
                case '--preset':
                case '--alt-preset':
                case '--r3mix':
                    if ($exploded_options[1] == 'fast') {
                        $exploded_options[1] .= ' '.$exploded_options[2];
                    }
                    switch ($exploded_options[1]) {
                        case 'portable':
                        case 'medium':
                        case 'standard':
                        case 'extreme':
                        case 'insane':
                        case 'fast portable':
                        case 'fast medium':
                        case 'fast standard':
                        case 'fast extreme':
                        case 'fast insane':
                        case 'r3mix':
                            static $expected_lowpass = array (
                                    'insane|20500'        => 20500,
                                    'insane|20600'        => 20600,  // 3.90.2, 3.90.3, 3.91
                                    'medium|18000'        => 18000,
                                    'fast medium|18000'   => 18000,
                                    'extreme|19500'       => 19500,  // 3.90,   3.90.1, 3.92, 3.95
                                    'extreme|19600'       => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
                                    'fast extreme|19500'  => 19500,  // 3.90,   3.90.1, 3.92, 3.95
                                    'fast extreme|19600'  => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
                                    'standard|19000'      => 19000,
                                    'fast standard|19000' => 19000,
                                    'r3mix|19500'         => 19500,  // 3.90,   3.90.1, 3.92
                                    'r3mix|19600'         => 19600,  // 3.90.2, 3.90.3, 3.91
                                    'r3mix|18000'         => 18000,  // 3.94,   3.95
                                );
                            if (!isset($expected_lowpass[$exploded_options[1].'|'.$thisfile_mpeg_audio_lame['lowpass_frequency']]) && ($thisfile_mpeg_audio_lame['lowpass_frequency'] < 22050) && (round($thisfile_mpeg_audio_lame['lowpass_frequency'] / 1000) < round($thisfile_mpeg_audio['sample_rate'] / 2000))) {
                                $encoder_options .= ' --lowpass '.$thisfile_mpeg_audio_lame['lowpass_frequency'];
                            }
                            break;

                        default:
                            break;
                    }
                    break;
            }
        }

        if (isset($thisfile_mpeg_audio_lame['raw']['source_sample_freq'])) {
            if (($thisfile_mpeg_audio['sample_rate'] == 44100) && ($thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 1)) {
                $encoder_options .= ' --resample 44100';
            } elseif (($thisfile_mpeg_audio['sample_rate'] == 48000) && ($thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 2)) {
                $encoder_options .= ' --resample 48000';
            } elseif ($thisfile_mpeg_audio['sample_rate'] < 44100) {
                switch ($thisfile_mpeg_audio_lame['raw']['source_sample_freq']) {
                    case 0: // <= 32000
                        // may or may not be same as source frequency - ignore
                        break;
                    case 1: // 44100
                    case 2: // 48000
                    case 3: // 48000+
                        $exploded_options = explode(' ', $encoder_options, 4);
                        switch ($exploded_options[0]) {
                            case '--preset':
                            case '--alt-preset':
                                switch ($exploded_options[1]) {
                                    case 'fast':
                                    case 'portable':
                                    case 'medium':
                                    case 'standard':
                                    case 'extreme':
                                    case 'insane':
                                        $encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
                                        break;

                                    default:
                                        static $expected_resampled_rate = array (
                                                'phon+/lw/mw-eu/sw|16000' => 16000,
                                                'mw-us|24000'             => 24000, // 3.95
                                                'mw-us|32000'             => 32000, // 3.93
                                                'mw-us|16000'             => 16000, // 3.92
                                                'phone|16000'             => 16000,
                                                'phone|11025'             => 11025, // 3.94a15
                                                'radio|32000'             => 32000, // 3.94a15
                                                'fm/radio|32000'          => 32000, // 3.92
                                                'fm|32000'                => 32000, // 3.90
                                                'voice|32000'             => 32000);
                                        if (!isset($expected_resampled_rate[$exploded_options[1].'|'.$thisfile_mpeg_audio['sample_rate']])) {
                                            $encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
                                        }
                                        break;
                                }
                                break;

                            case '--r3mix':
                            default:
                                $encoder_options .= ' --resample '.$thisfile_mpeg_audio['sample_rate'];
                                break;
                        }
                        break;
                }
            }
        }
        if (empty($encoder_options) && !empty($info['audio']['bitrate']) && !empty($info['audio']['bitrate_mode'])) {
            //$encoder_options = strtoupper($info['audio']['bitrate_mode']).ceil($info['audio']['bitrate'] / 1000);
            $encoder_options = strtoupper($info['audio']['bitrate_mode']);
        }

        return $encoder_options;
    }


    
    public function decodeMPEGaudioHeader($fd, $offset, &$info, $recursive_search=true, $scan_as_cbr=false, $fast_mpeg_header_scan=false) {

        static $mpeg_audio_version_lookup;
        static $mpeg_audio_layer_lookup;
        static $mpeg_audio_bitrate_lookup;
        static $mpeg_audio_frequency_lookup;
        static $mpeg_audio_channel_mode_lookup;
        static $mpeg_audio_mode_extension_lookup;
        static $mpeg_audio_emphasis_lookup;
        if (empty($mpeg_audio_version_lookup)) {
            $mpeg_audio_version_lookup        = getid3_mp3::MPEGaudioVersionarray();
            $mpeg_audio_layer_lookup          = getid3_mp3::MPEGaudioLayerarray();
            $mpeg_audio_bitrate_lookup        = getid3_mp3::MPEGaudioBitratearray();
            $mpeg_audio_frequency_lookup      = getid3_mp3::MPEGaudioFrequencyarray();
            $mpeg_audio_channel_mode_lookup   = getid3_mp3::MPEGaudioChannelModearray();
            $mpeg_audio_mode_extension_lookup = getid3_mp3::MPEGaudioModeExtensionarray();
            $mpeg_audio_emphasis_lookup       = getid3_mp3::MPEGaudioEmphasisarray();
        }

        if ($offset >= $info['avdataend']) {

            // non-fatal error: 'end of file encounter looking for MPEG synch'
            return;
            
        }
        fseek($fd, $offset, SEEK_SET);
        $header_string = fread($fd, 226); // LAME header at offset 36 + 190 bytes of Xing/LAME data

        // MP3 audio frame structure:
        // $aa $aa $aa $aa [$bb $bb] $cc...
        // where $aa..$aa is the four-byte mpeg-audio header (below)
        // $bb $bb is the optional 2-byte CRC
        // and $cc... is the audio data

        $head4 = substr($header_string, 0, 4);

        if (isset($mpeg_audio_header_decode_cache[$head4])) {
            $mpeg_header_raw_array= $mpeg_audio_header_decode_cache[$head4];
        } else {
            $mpeg_header_raw_array = getid3_mp3::MPEGaudioHeaderDecode($head4);
            $mpeg_audio_header_decode_cache[$head4] = $mpeg_header_raw_array;
        }

        // Not in cache
        if (!isset($mpeg_audio_header_valid_cache[$head4])) {
            $mpeg_audio_header_valid_cache[$head4] = getid3_mp3::MPEGaudioHeaderValid($mpeg_header_raw_array, false, false);
        }

        // shortcut
        if (!isset($info['mpeg']['audio'])) {
            $info['mpeg']['audio'] = array ();
        }
        $thisfile_mpeg_audio = &$info['mpeg']['audio'];


        if ($mpeg_audio_header_valid_cache[$head4]) {
            $thisfile_mpeg_audio['raw'] = $mpeg_header_raw_array;
        } else {
            
            // non-fatal error: Invalid MPEG audio header at offset $offset
            return;
        }

        if (!$fast_mpeg_header_scan) {

            $thisfile_mpeg_audio['version']       = $mpeg_audio_version_lookup[$thisfile_mpeg_audio['raw']['version']];
            $thisfile_mpeg_audio['layer']         = $mpeg_audio_layer_lookup[$thisfile_mpeg_audio['raw']['layer']];

            $thisfile_mpeg_audio['channelmode']   = $mpeg_audio_channel_mode_lookup[$thisfile_mpeg_audio['raw']['channelmode']];
            $thisfile_mpeg_audio['channels']      = (($thisfile_mpeg_audio['channelmode'] == 'mono') ? 1 : 2);
            $thisfile_mpeg_audio['sample_rate']   = $mpeg_audio_frequency_lookup[$thisfile_mpeg_audio['version']][$thisfile_mpeg_audio['raw']['sample_rate']];
            $thisfile_mpeg_audio['protection']    = !$thisfile_mpeg_audio['raw']['protection'];
            $thisfile_mpeg_audio['private']       = (bool) $thisfile_mpeg_audio['raw']['private'];
            $thisfile_mpeg_audio['modeextension'] = $mpeg_audio_mode_extension_lookup[$thisfile_mpeg_audio['layer']][$thisfile_mpeg_audio['raw']['modeextension']];
            $thisfile_mpeg_audio['copyright']     = (bool) $thisfile_mpeg_audio['raw']['copyright'];
            $thisfile_mpeg_audio['original']      = (bool) $thisfile_mpeg_audio['raw']['original'];
            $thisfile_mpeg_audio['emphasis']      = $mpeg_audio_emphasis_lookup[$thisfile_mpeg_audio['raw']['emphasis']];

            $info['audio']['channels']    = $thisfile_mpeg_audio['channels'];
            $info['audio']['sample_rate'] = $thisfile_mpeg_audio['sample_rate'];

            if ($thisfile_mpeg_audio['protection']) {
                $thisfile_mpeg_audio['crc'] = getid3_lib::BigEndian2Int(substr($header_string, 4, 2));
            }

        }

        if ($thisfile_mpeg_audio['raw']['bitrate'] == 15) {
            // http://www.hydrogenaudio.org/?act=ST&f=16&t=9682&st=0
            $this->getid3->warning('Invalid bitrate index (15), this is a known bug in free-format MP3s encoded by LAME v3.90 - 3.93.1');
            $thisfile_mpeg_audio['raw']['bitrate'] = 0;
        }
        $thisfile_mpeg_audio['padding'] = (bool) $thisfile_mpeg_audio['raw']['padding'];
        $thisfile_mpeg_audio['bitrate'] = $mpeg_audio_bitrate_lookup[$thisfile_mpeg_audio['version']][$thisfile_mpeg_audio['layer']][$thisfile_mpeg_audio['raw']['bitrate']];

        if (($thisfile_mpeg_audio['bitrate'] == 'free') && ($offset == $info['avdataoffset'])) {
            // only skip multiple frame check if free-format bitstream found at beginning of file
            // otherwise is quite possibly simply corrupted data
            $recursive_search = false;
        }

        // For Layer 2 there are some combinations of bitrate and mode which are not allowed.
        if (!$fast_mpeg_header_scan && ($thisfile_mpeg_audio['layer'] == '2')) {

            $info['audio']['dataformat'] = 'mp2';
            switch ($thisfile_mpeg_audio['channelmode']) {

                case 'mono':
                    if (($thisfile_mpeg_audio['bitrate'] == 'free') || ($thisfile_mpeg_audio['bitrate'] <= 192000)) {
                        // these are ok
                    } else {
                        
                        // non-fatal error: bitrate not allowed in Layer 2/mono
                        return;
                    }
                    break;

                case 'stereo':
                case 'joint stereo':
                case 'dual channel':
                    if (($thisfile_mpeg_audio['bitrate'] == 'free') || ($thisfile_mpeg_audio['bitrate'] == 64000) || ($thisfile_mpeg_audio['bitrate'] >= 96000)) {
                        // these are ok
                    } else {
                        
                        // non-fatal error: bitrate not allowed in Layer 2/stereo/joint stereo/dual channel
                        return;
                    }
                    break;

            }

        }


        if ($info['audio']['sample_rate'] > 0) {
            $thisfile_mpeg_audio['framelength'] = getid3_mp3::MPEGaudioFrameLength($thisfile_mpeg_audio['bitrate'], $thisfile_mpeg_audio['version'], $thisfile_mpeg_audio['layer'], (int) $thisfile_mpeg_audio['padding'], $info['audio']['sample_rate']);
        }

        $next_frame_test_offset = $offset + 1;
        if ($thisfile_mpeg_audio['bitrate'] != 'free') {

            $info['audio']['bitrate'] = $thisfile_mpeg_audio['bitrate'];

            if (isset($thisfile_mpeg_audio['framelength'])) {
                $next_frame_test_offset = $offset + $thisfile_mpeg_audio['framelength'];
            } else {

                // non-fatal error: Frame at offset('.$offset.') is has an invalid frame length.
                return;
            }

        }

        $expected_number_of_audio_bytes = 0;

        ////////////////////////////////////////////////////////////////////////////////////
        // Variable-bitrate headers

        if (substr($header_string, 4 + 32, 4) == 'VBRI') {
            // Fraunhofer VBR header is hardcoded 'VBRI' at offset 0x24 (36)
            // specs taken from http://minnie.tuhs.org/pipermail/mp3encoder/2001-January/001800.html

            $thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
            $thisfile_mpeg_audio['VBR_method']   = 'Fraunhofer';
            $info['audio']['codec']                = 'Fraunhofer';

            $side_info_data = substr($header_string, 4 + 2, 32);

            $fraunhofer_vbr_offset = 36;

            $thisfile_mpeg_audio['VBR_encoder_version']     = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset +  4, 2)); // VbriVersion
            $thisfile_mpeg_audio['VBR_encoder_delay']       = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset +  6, 2)); // VbriDelay
            $thisfile_mpeg_audio['VBR_quality']             = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset +  8, 2)); // VbriQuality
            $thisfile_mpeg_audio['VBR_bytes']               = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 10, 4)); // VbriStreamBytes
            $thisfile_mpeg_audio['VBR_frames']              = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 14, 4)); // VbriStreamFrames
            $thisfile_mpeg_audio['VBR_seek_offsets']        = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 18, 2)); // VbriTableSize
            $thisfile_mpeg_audio['VBR_seek_scale']          = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 20, 2)); // VbriTableScale
            $thisfile_mpeg_audio['VBR_entry_bytes']         = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 22, 2)); // VbriEntryBytes
            $thisfile_mpeg_audio['VBR_entry_frames']        = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset + 24, 2)); // VbriEntryFrames

            $expected_number_of_audio_bytes = $thisfile_mpeg_audio['VBR_bytes'];

            $previous_byte_offset = $offset;
            for ($i = 0; $i < $thisfile_mpeg_audio['VBR_seek_offsets']; $i++) {
                $fraunhofer_offset_n = getid3_lib::BigEndian2Int(substr($header_string, $fraunhofer_vbr_offset, $thisfile_mpeg_audio['VBR_entry_bytes']));
                $fraunhofer_vbr_offset += $thisfile_mpeg_audio['VBR_entry_bytes'];
                $thisfile_mpeg_audio['VBR_offsets_relative'][$i] = ($fraunhofer_offset_n * $thisfile_mpeg_audio['VBR_seek_scale']);
                $thisfile_mpeg_audio['VBR_offsets_absolute'][$i] = ($fraunhofer_offset_n * $thisfile_mpeg_audio['VBR_seek_scale']) + $previous_byte_offset;
                $previous_byte_offset += $fraunhofer_offset_n;
            }


        } else {

            // Xing VBR header is hardcoded 'Xing' at a offset 0x0D (13), 0x15 (21) or 0x24 (36)
            // depending on MPEG layer and number of channels

            $vbr_id_offset = getid3_mp3::XingVBRidOffset($thisfile_mpeg_audio['version'], $thisfile_mpeg_audio['channelmode']);
            $side_info_data = substr($header_string, 4 + 2, $vbr_id_offset - 4);

            if ((substr($header_string, $vbr_id_offset, strlen('Xing')) == 'Xing') || (substr($header_string, $vbr_id_offset, strlen('Info')) == 'Info')) {
                // 'Xing' is traditional Xing VBR frame
                // 'Info' is LAME-encoded CBR (This was done to avoid CBR files to be recognized as traditional Xing VBR files by some decoders.)
                // 'Info' *can* legally be used to specify a VBR file as well, however.

                // http://www.multiweb.cz/twoinches/MP3inside.htm
                //00..03 = "Xing" or "Info"
                //04..07 = Flags:
                //  0x01  Frames Flag     set if value for number of frames in file is stored
                //  0x02  Bytes Flag      set if value for filesize in bytes is stored
                //  0x04  TOC Flag        set if values for TOC are stored
                //  0x08  VBR Scale Flag  set if values for VBR scale is stored
                //08..11  Frames: Number of frames in file (including the first Xing/Info one)
                //12..15  Bytes:  File length in Bytes
                //16..115  TOC (Table of Contents):
                //  Contains of 100 indexes (one Byte length) for easier lookup in file. Approximately solves problem with moving inside file.
                //  Each Byte has a value according this formula:
                //  (TOC[i] / 256) * fileLenInBytes
                //  So if song lasts eg. 240 sec. and you want to jump to 60. sec. (and file is 5 000 000 Bytes length) you can use:
                //  TOC[(60/240)*100] = TOC[25]
                //  and corresponding Byte in file is then approximately at:
                //  (TOC[25]/256) * 5000000
                //116..119  VBR Scale


                // should be safe to leave this at 'vbr' and let it be overriden to 'cbr' if a CBR preset/mode is used by LAME
                $thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
                $thisfile_mpeg_audio['VBR_method']   = 'Xing';

                $thisfile_mpeg_audio['xing_flags_raw'] = getid3_lib::BigEndian2Int(substr($header_string, $vbr_id_offset + 4, 4));

                $thisfile_mpeg_audio['xing_flags']['frames']    = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000001);
                $thisfile_mpeg_audio['xing_flags']['bytes']     = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000002);
                $thisfile_mpeg_audio['xing_flags']['toc']       = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000004);
                $thisfile_mpeg_audio['xing_flags']['vbr_scale'] = (bool) ($thisfile_mpeg_audio['xing_flags_raw'] & 0x00000008);

                if ($thisfile_mpeg_audio['xing_flags']['frames']) {
                    $thisfile_mpeg_audio['VBR_frames'] = getid3_lib::BigEndian2Int(substr($header_string, $vbr_id_offset +  8, 4));
                }
                if ($thisfile_mpeg_audio['xing_flags']['bytes']) {
                    $thisfile_mpeg_audio['VBR_bytes']  = getid3_lib::BigEndian2Int(substr($header_string, $vbr_id_offset + 12, 4));
                }

                if (!empty($thisfile_mpeg_audio['VBR_frames']) && !empty($thisfile_mpeg_audio['VBR_bytes'])) {

                    $frame_lengthfloat = $thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames'];

                    if ($thisfile_mpeg_audio['layer'] == '1') {
                        // BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
                        $info['audio']['bitrate'] = ($frame_lengthfloat / 4) * $thisfile_mpeg_audio['sample_rate'] * (2 / $info['audio']['channels']) / 12;
                    } else {
                        // Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
                        $info['audio']['bitrate'] = $frame_lengthfloat * $thisfile_mpeg_audio['sample_rate'] * (2 / $info['audio']['channels']) / 144;
                    }
                    $thisfile_mpeg_audio['framelength'] = floor($frame_lengthfloat);
                }

                if ($thisfile_mpeg_audio['xing_flags']['toc']) {
                    $lame_toc_data = substr($header_string, $vbr_id_offset + 16, 100);
                    for ($i = 0; $i < 100; $i++) {
                        $thisfile_mpeg_audio['toc'][$i] = ord($lame_toc_data{$i});
                    }
                }
                if ($thisfile_mpeg_audio['xing_flags']['vbr_scale']) {
                    $thisfile_mpeg_audio['VBR_scale'] = getid3_lib::BigEndian2Int(substr($header_string, $vbr_id_offset + 116, 4));
                }


                // http://gabriel.mp3-tech.org/mp3infotag.html
                if (substr($header_string, $vbr_id_offset + 120, 4) == 'LAME') {

                    // shortcut
                    $thisfile_mpeg_audio['LAME'] = array ();
                    $thisfile_mpeg_audio_lame    = &$thisfile_mpeg_audio['LAME'];


                    $thisfile_mpeg_audio_lame['long_version']  = substr($header_string, $vbr_id_offset + 120, 20);
                    $thisfile_mpeg_audio_lame['short_version'] = substr($thisfile_mpeg_audio_lame['long_version'], 0, 9);

                    if ($thisfile_mpeg_audio_lame['short_version'] >= 'LAME3.90') {

                        // extra 11 chars are not part of version string when LAMEtag present
                        unset($thisfile_mpeg_audio_lame['long_version']);

                        // It the LAME tag was only introduced in LAME v3.90
                        // http://www.hydrogenaudio.org/?act=ST&f=15&t=9933

                        // Offsets of various bytes in http://gabriel.mp3-tech.org/mp3infotag.html
                        // are assuming a 'Xing' identifier offset of 0x24, which is the case for
                        // MPEG-1 non-mono, but not for other combinations
                        $lame_tag_offset_contant = $vbr_id_offset - 0x24;

                        // shortcuts
                        $thisfile_mpeg_audio_lame['RGAD']    = array ('track'=>array(), 'album'=>array());
                        $thisfile_mpeg_audio_lame_rgad       = &$thisfile_mpeg_audio_lame['RGAD'];
                        $thisfile_mpeg_audio_lame_rgad_track = &$thisfile_mpeg_audio_lame_rgad['track'];
                        $thisfile_mpeg_audio_lame_rgad_album = &$thisfile_mpeg_audio_lame_rgad['album'];
                        $thisfile_mpeg_audio_lame['raw']     = array ();
                        $thisfile_mpeg_audio_lame_raw        = &$thisfile_mpeg_audio_lame['raw'];

                        // byte $9B  VBR Quality
                        // This field is there to indicate a quality level, although the scale was not precised in the original Xing specifications.
                        // Actually overwrites original Xing bytes
                        unset($thisfile_mpeg_audio['VBR_scale']);
                        $thisfile_mpeg_audio_lame['vbr_quality'] = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0x9B, 1));

                        // bytes $9C-$A4  Encoder short VersionString
                        $thisfile_mpeg_audio_lame['short_version'] = substr($header_string, $lame_tag_offset_contant + 0x9C, 9);

                        // byte $A5  Info Tag revision + VBR method
                        $lame_tagRevisionVBRmethod = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xA5, 1));

                        $thisfile_mpeg_audio_lame['tag_revision']      = ($lame_tagRevisionVBRmethod & 0xF0) >> 4;
                        $thisfile_mpeg_audio_lame_raw['vbr_method'] =  $lame_tagRevisionVBRmethod & 0x0F;
                        $thisfile_mpeg_audio_lame['vbr_method']        = getid3_mp3::LAMEvbrMethodLookup($thisfile_mpeg_audio_lame_raw['vbr_method']);
                        $thisfile_mpeg_audio['bitrate_mode']           = substr($thisfile_mpeg_audio_lame['vbr_method'], 0, 3); // usually either 'cbr' or 'vbr', but truncates 'vbr-old / vbr-rh' to 'vbr'

                        // byte $A6  Lowpass filter value
                        $thisfile_mpeg_audio_lame['lowpass_frequency'] = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xA6, 1)) * 100;

                        // bytes $A7-$AE  Replay Gain
                        // http://privatewww.essex.ac.uk/~djmrob/replaygain/rg_data_format.html
                        // bytes $A7-$AA : 32 bit floating point "Peak signal amplitude"
                        if ($thisfile_mpeg_audio_lame['short_version'] >= 'LAME3.94b') {
                            // LAME 3.94a16 and later - 9.23 fixed point
                            // ie 0x0059E2EE / (2^23) = 5890798 / 8388608 = 0.7022378444671630859375
                            $thisfile_mpeg_audio_lame_rgad['peak_amplitude'] = (float) ((getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xA7, 4))) / 8388608);
                        } else {
                            // LAME 3.94a15 and earlier - 32-bit floating point
                            // Actually 3.94a16 will fall in here too and be WRONG, but is hard to detect 3.94a16 vs 3.94a15
                            $thisfile_mpeg_audio_lame_rgad['peak_amplitude'] = getid3_lib::LittleEndian2Float(substr($header_string, $lame_tag_offset_contant + 0xA7, 4));
                        }
                        if ($thisfile_mpeg_audio_lame_rgad['peak_amplitude'] == 0) {
                            unset($thisfile_mpeg_audio_lame_rgad['peak_amplitude']);
                        } else {
                            $thisfile_mpeg_audio_lame_rgad['peak_db'] = 20 * log10($thisfile_mpeg_audio_lame_rgad['peak_amplitude']);
                        }

                        $thisfile_mpeg_audio_lame_raw['RGAD_track']      =   getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xAB, 2));
                        $thisfile_mpeg_audio_lame_raw['RGAD_album']      =   getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xAD, 2));


                        if ($thisfile_mpeg_audio_lame_raw['RGAD_track'] != 0) {

                            $thisfile_mpeg_audio_lame_rgad_track['raw']['name']        = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0xE000) >> 13;
                            $thisfile_mpeg_audio_lame_rgad_track['raw']['originator']  = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x1C00) >> 10;
                            $thisfile_mpeg_audio_lame_rgad_track['raw']['sign_bit']    = ($thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x0200) >> 9;
                            $thisfile_mpeg_audio_lame_rgad_track['raw']['gain_adjust'] =  $thisfile_mpeg_audio_lame_raw['RGAD_track'] & 0x01FF;
                            $thisfile_mpeg_audio_lame_rgad_track['name']       = getid3_lib_replaygain::NameLookup($thisfile_mpeg_audio_lame_rgad_track['raw']['name']);
                            $thisfile_mpeg_audio_lame_rgad_track['originator'] = getid3_lib_replaygain::OriginatorLookup($thisfile_mpeg_audio_lame_rgad_track['raw']['originator']);
                            $thisfile_mpeg_audio_lame_rgad_track['gain_db']    = getid3_lib_replaygain::AdjustmentLookup($thisfile_mpeg_audio_lame_rgad_track['raw']['gain_adjust'], $thisfile_mpeg_audio_lame_rgad_track['raw']['sign_bit']);

                            if (!empty($thisfile_mpeg_audio_lame_rgad['peak_amplitude'])) {
                                $info['replay_gain']['track']['peak']   = $thisfile_mpeg_audio_lame_rgad['peak_amplitude'];
                            }
                            $info['replay_gain']['track']['originator'] = $thisfile_mpeg_audio_lame_rgad_track['originator'];
                            $info['replay_gain']['track']['adjustment'] = $thisfile_mpeg_audio_lame_rgad_track['gain_db'];
                        } else {
                            unset($thisfile_mpeg_audio_lame_rgad['track']);
                        }
                        if ($thisfile_mpeg_audio_lame_raw['RGAD_album'] != 0) {

                            $thisfile_mpeg_audio_lame_rgad_album['raw']['name']        = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0xE000) >> 13;
                            $thisfile_mpeg_audio_lame_rgad_album['raw']['originator']  = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x1C00) >> 10;
                            $thisfile_mpeg_audio_lame_rgad_album['raw']['sign_bit']    = ($thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x0200) >> 9;
                            $thisfile_mpeg_audio_lame_rgad_album['raw']['gain_adjust'] =  $thisfile_mpeg_audio_lame_raw['RGAD_album'] & 0x01FF;
                            $thisfile_mpeg_audio_lame_rgad_album['name']       = getid3_lib_replaygain::NameLookup($thisfile_mpeg_audio_lame_rgad_album['raw']['name']);
                            $thisfile_mpeg_audio_lame_rgad_album['originator'] = getid3_lib_replaygain::OriginatorLookup($thisfile_mpeg_audio_lame_rgad_album['raw']['originator']);
                            $thisfile_mpeg_audio_lame_rgad_album['gain_db']    = getid3_lib_replaygain::AdjustmentLookup($thisfile_mpeg_audio_lame_rgad_album['raw']['gain_adjust'], $thisfile_mpeg_audio_lame_rgad_album['raw']['sign_bit']);

                            if (!empty($thisfile_mpeg_audio_lame_rgad['peak_amplitude'])) {
                                $info['replay_gain']['album']['peak']   = $thisfile_mpeg_audio_lame_rgad['peak_amplitude'];
                            }
                            $info['replay_gain']['album']['originator'] = $thisfile_mpeg_audio_lame_rgad_album['originator'];
                            $info['replay_gain']['album']['adjustment'] = $thisfile_mpeg_audio_lame_rgad_album['gain_db'];
                        } else {
                            unset($thisfile_mpeg_audio_lame_rgad['album']);
                        }
                        if (empty($thisfile_mpeg_audio_lame_rgad)) {
                            unset($thisfile_mpeg_audio_lame['RGAD']);
                        }


                        // byte $AF  Encoding flags + ATH Type
                        $encoding_flags_ath_type = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xAF, 1));
                        $thisfile_mpeg_audio_lame['encoding_flags']['nspsytune']   = (bool) ($encoding_flags_ath_type & 0x10);
                        $thisfile_mpeg_audio_lame['encoding_flags']['nssafejoint'] = (bool) ($encoding_flags_ath_type & 0x20);
                        $thisfile_mpeg_audio_lame['encoding_flags']['nogap_next']  = (bool) ($encoding_flags_ath_type & 0x40);
                        $thisfile_mpeg_audio_lame['encoding_flags']['nogap_prev']  = (bool) ($encoding_flags_ath_type & 0x80);
                        $thisfile_mpeg_audio_lame['ath_type']                      =         $encoding_flags_ath_type & 0x0F;

                        // byte $B0  if ABR {specified bitrate} else {minimal bitrate}
                        $thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'] = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB0, 1));
                        if ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 2) { // Average BitRate (ABR)
                            $thisfile_mpeg_audio_lame['bitrate_abr'] = $thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'];
                        } elseif ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 1) { // Constant BitRate (CBR)
                            // ignore
                        } elseif ($thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'] > 0) { // Variable BitRate (VBR) - minimum bitrate
                            $thisfile_mpeg_audio_lame['bitrate_min'] = $thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate'];
                        }

                        // bytes $B1-$B3  Encoder delays
                        $encoder_delays = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB1, 3));
                        $thisfile_mpeg_audio_lame['encoder_delay'] = ($encoder_delays & 0xFFF000) >> 12;
                        $thisfile_mpeg_audio_lame['end_padding']   =  $encoder_delays & 0x000FFF;

                        // byte $B4  Misc
                        $misc_byte = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB4, 1));
                        $thisfile_mpeg_audio_lame_raw['noise_shaping']       = ($misc_byte & 0x03);
                        $thisfile_mpeg_audio_lame_raw['stereo_mode']         = ($misc_byte & 0x1C) >> 2;
                        $thisfile_mpeg_audio_lame_raw['not_optimal_quality'] = ($misc_byte & 0x20) >> 5;
                        $thisfile_mpeg_audio_lame_raw['source_sample_freq']  = ($misc_byte & 0xC0) >> 6;
                        $thisfile_mpeg_audio_lame['noise_shaping']           = $thisfile_mpeg_audio_lame_raw['noise_shaping'];
                        $thisfile_mpeg_audio_lame['stereo_mode']             = getid3_mp3::LAMEmiscStereoModeLookup($thisfile_mpeg_audio_lame_raw['stereo_mode']);
                        $thisfile_mpeg_audio_lame['not_optimal_quality']     = (bool) $thisfile_mpeg_audio_lame_raw['not_optimal_quality'];
                        $thisfile_mpeg_audio_lame['source_sample_freq']      = getid3_mp3::LAMEmiscSourceSampleFrequencyLookup($thisfile_mpeg_audio_lame_raw['source_sample_freq']);

                        // byte $B5  MP3 Gain
                        $thisfile_mpeg_audio_lame_raw['mp3_gain']   = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB5, 1), false, true);
                        $thisfile_mpeg_audio_lame['mp3_gain_db']     = (20 * log10(2) / 4) * $thisfile_mpeg_audio_lame_raw['mp3_gain'];
                        $thisfile_mpeg_audio_lame['mp3_gain_factor'] = pow(2, ($thisfile_mpeg_audio_lame['mp3_gain_db'] / 6));

                        // bytes $B6-$B7  Preset and surround info
                        $PresetSurroundBytes = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB6, 2));
                        // Reserved                                                    = ($PresetSurroundBytes & 0xC000);
                        $thisfile_mpeg_audio_lame_raw['surround_info'] = ($PresetSurroundBytes & 0x3800);
                        $thisfile_mpeg_audio_lame['surround_info']     = getid3_mp3::LAMEsurroundInfoLookup($thisfile_mpeg_audio_lame_raw['surround_info']);
                        $thisfile_mpeg_audio_lame['preset_used_id']    = ($PresetSurroundBytes & 0x07FF);
                        $thisfile_mpeg_audio_lame['preset_used']       = getid3_mp3::LAMEpresetUsedLookup($thisfile_mpeg_audio_lame);
                        if (!empty($thisfile_mpeg_audio_lame['preset_used_id']) && empty($thisfile_mpeg_audio_lame['preset_used'])) {
                            $this->getid3->warning('Unknown LAME preset used ('.$thisfile_mpeg_audio_lame['preset_used_id'].') - please report to info@getid3.org');
                        }
                        if (($thisfile_mpeg_audio_lame['short_version'] == 'LAME3.90.') && !empty($thisfile_mpeg_audio_lame['preset_used_id'])) {
                            // this may change if 3.90.4 ever comes out
                            $thisfile_mpeg_audio_lame['short_version'] = 'LAME3.90.3';
                        }

                        // bytes $B8-$BB  MusicLength
                        $thisfile_mpeg_audio_lame['audio_bytes'] = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xB8, 4));
                        $expected_number_of_audio_bytes = (($thisfile_mpeg_audio_lame['audio_bytes'] > 0) ? $thisfile_mpeg_audio_lame['audio_bytes'] : $thisfile_mpeg_audio['VBR_bytes']);

                        // bytes $BC-$BD  MusicCRC
                        $thisfile_mpeg_audio_lame['music_crc']    = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xBC, 2));

                        // bytes $BE-$BF  CRC-16 of Info Tag
                        $thisfile_mpeg_audio_lame['lame_tag_crc'] = getid3_lib::BigEndian2Int(substr($header_string, $lame_tag_offset_contant + 0xBE, 2));


                        // LAME CBR
                        if ($thisfile_mpeg_audio_lame_raw['vbr_method'] == 1) {

                            $thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
                            $thisfile_mpeg_audio['bitrate'] = getid3_mp3::ClosestStandardMP3Bitrate($thisfile_mpeg_audio['bitrate']);
                            $info['audio']['bitrate'] = $thisfile_mpeg_audio['bitrate'];

                        }

                    }
                }

            } else {

                // not Fraunhofer or Xing VBR methods, most likely CBR (but could be VBR with no header)
                $thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
                if ($recursive_search) {
                    $thisfile_mpeg_audio['bitrate_mode'] = 'vbr';
                    if (getid3_mp3::RecursiveFrameScanning($fd, $info, $offset, $next_frame_test_offset, true)) {
                        $recursive_search = false;
                        $thisfile_mpeg_audio['bitrate_mode'] = 'cbr';
                    }
                    if ($thisfile_mpeg_audio['bitrate_mode'] == 'vbr') {
                        $this->getid3->warning('VBR file with no VBR header. Bitrate values calculated from actual frame bitrates.');
                    }
                }

            }

        }

        if (($expected_number_of_audio_bytes > 0) && ($expected_number_of_audio_bytes != ($info['avdataend'] - $info['avdataoffset']))) {
            if ($expected_number_of_audio_bytes > ($info['avdataend'] - $info['avdataoffset'])) {
                if (($expected_number_of_audio_bytes - ($info['avdataend'] - $info['avdataoffset'])) == 1) {
                    $this->getid3->warning('Last byte of data truncated (this is a known bug in Meracl ID3 Tag Writer before v1.3.5)');
                } else {
                    $this->getid3->warning('Probable truncated file: expecting '.$expected_number_of_audio_bytes.' bytes of audio data, only found '.($info['avdataend'] - $info['avdataoffset']).' (short by '.($expected_number_of_audio_bytes - ($info['avdataend'] - $info['avdataoffset'])).' bytes)');
                }
            } else {
                if ((($info['avdataend'] - $info['avdataoffset']) - $expected_number_of_audio_bytes) == 1) {
                        $info['avdataend']--;
                } else {
                    $this->getid3->warning('Too much data in file: expecting '.$expected_number_of_audio_bytes.' bytes of audio data, found '.($info['avdataend'] - $info['avdataoffset']).' ('.(($info['avdataend'] - $info['avdataoffset']) - $expected_number_of_audio_bytes).' bytes too many)');
                }
            }
        }

        if (($thisfile_mpeg_audio['bitrate'] == 'free') && empty($info['audio']['bitrate'])) {
            if (($offset == $info['avdataoffset']) && empty($thisfile_mpeg_audio['VBR_frames'])) {
                $frame_byte_length = getid3_mp3::FreeFormatFrameLength($fd, $offset, $info, true);
                if ($frame_byte_length > 0) {
                    $thisfile_mpeg_audio['framelength'] = $frame_byte_length;
                    if ($thisfile_mpeg_audio['layer'] == '1') {
                        // BitRate = (((FrameLengthInBytes / 4) - Padding) * SampleRate) / 12
                        $info['audio']['bitrate'] = ((($frame_byte_length / 4) - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 12;
                    } else {
                        // Bitrate = ((FrameLengthInBytes - Padding) * SampleRate) / 144
                        $info['audio']['bitrate'] = (($frame_byte_length - intval($thisfile_mpeg_audio['padding'])) * $thisfile_mpeg_audio['sample_rate']) / 144;
                    }
                } else {
                    
                    // non-fatal error: Error calculating frame length of free-format MP3 without Xing/LAME header.
                    return;
                }
            }
        }

        if (@$thisfile_mpeg_audio['VBR_frames']) {
            switch ($thisfile_mpeg_audio['bitrate_mode']) {
                case 'vbr':
                case 'abr':
                    if (($thisfile_mpeg_audio['version'] == '1') && ($thisfile_mpeg_audio['layer'] == 1)) {
                        $thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($info['audio']['sample_rate'] / 384);
                    } elseif ((($thisfile_mpeg_audio['version'] == '2') || ($thisfile_mpeg_audio['version'] == '2.5')) && ($thisfile_mpeg_audio['layer'] == 3)) {
                        $thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($info['audio']['sample_rate'] / 576);
                    } else {
                        $thisfile_mpeg_audio['VBR_bitrate'] = ((@$thisfile_mpeg_audio['VBR_bytes'] / $thisfile_mpeg_audio['VBR_frames']) * 8) * ($info['audio']['sample_rate'] / 1152);
                    }
                    if ($thisfile_mpeg_audio['VBR_bitrate'] > 0) {
                        $info['audio']['bitrate']         = $thisfile_mpeg_audio['VBR_bitrate'];
                        $thisfile_mpeg_audio['bitrate'] = $thisfile_mpeg_audio['VBR_bitrate']; // to avoid confusion
                    }
                    break;
            }
        }

        // End variable-bitrate headers
        ////////////////////////////////////////////////////////////////////////////////////

        if ($recursive_search) {

            if (!getid3_mp3::RecursiveFrameScanning($fd, $info, $offset, $next_frame_test_offset, $scan_as_cbr)) {
                return false;
            }

        }

        return true;
    }

    
    
    public function RecursiveFrameScanning(&$fd, &$info, &$offset, &$next_frame_test_offset, $scan_as_cbr) {
        for ($i = 0; $i < getid3_mp3::VALID_CHECK_FRAMES; $i++) {
            // check next getid3_mp3::VALID_CHECK_FRAMES frames for validity, to make sure we haven't run across a false synch
            if (($next_frame_test_offset + 4) >= $info['avdataend']) {
                // end of file
                return true;
            }

            $next_frame_test_array = array ('avdataend' => $info['avdataend'], 'avdataoffset' => $info['avdataoffset']);
            if ($this->decodeMPEGaudioHeader($fd, $next_frame_test_offset, $next_frame_test_array, false)) {
                if ($scan_as_cbr) {
                    // force CBR mode, used for trying to pick out invalid audio streams with
                    // valid(?) VBR headers, or VBR streams with no VBR header
                    if (!isset($next_frame_test_array['mpeg']['audio']['bitrate']) || !isset($info['mpeg']['audio']['bitrate']) || ($next_frame_test_array['mpeg']['audio']['bitrate'] != $info['mpeg']['audio']['bitrate'])) {
                        return false;
                    }
                }


                // next frame is OK, get ready to check the one after that
                if (isset($next_frame_test_array['mpeg']['audio']['framelength']) && ($next_frame_test_array['mpeg']['audio']['framelength'] > 0)) {
                    $next_frame_test_offset += $next_frame_test_array['mpeg']['audio']['framelength'];
                } else {
                    
                    // non-fatal error: Frame at offset $offset has an invalid frame length.
                    return;
                }

            } else {

                // non-fatal error: Next frame is not valid.
                return;
            }
        }
        return true;
    }

    
    
    public function FreeFormatFrameLength($fd, $offset, &$info, $deep_scan=false) {
        fseek($fd, $offset, SEEK_SET);
        $mpeg_audio_data = fread($fd, 32768);

        $sync_pattern1 = substr($mpeg_audio_data, 0, 4);
        // may be different pattern due to padding
        $sync_pattern2 = $sync_pattern1{0}.$sync_pattern1{1}.chr(ord($sync_pattern1{2}) | 0x02).$sync_pattern1{3};
        if ($sync_pattern2 === $sync_pattern1) {
            $sync_pattern2 = $sync_pattern1{0}.$sync_pattern1{1}.chr(ord($sync_pattern1{2}) & 0xFD).$sync_pattern1{3};
        }

        $frame_length = false;
        $frame_length1 = strpos($mpeg_audio_data, $sync_pattern1, 4);
        $frame_length2 = strpos($mpeg_audio_data, $sync_pattern2, 4);
        if ($frame_length1 > 4) {
            $frame_length = $frame_length1;
        }
        if (($frame_length2 > 4) && ($frame_length2 < $frame_length1)) {
            $frame_length = $frame_length2;
        }
        if (!$frame_length) {

            // LAME 3.88 has a different value for modeextension on the first frame vs the rest
            $frame_length1 = strpos($mpeg_audio_data, substr($sync_pattern1, 0, 3), 4);
            $frame_length2 = strpos($mpeg_audio_data, substr($sync_pattern2, 0, 3), 4);

            if ($frame_length1 > 4) {
                $frame_length = $frame_length1;
            }
            if (($frame_length2 > 4) && ($frame_length2 < $frame_length1)) {
                $frame_length = $frame_length2;
            }
            if (!$frame_length) {
                throw new getid3_exception('Cannot find next free-format synch pattern ('.getid3_lib::PrintHexBytes($sync_pattern1).' or '.getid3_lib::PrintHexBytes($sync_pattern2).') after offset '.$offset);
            } else {
                $this->getid3->warning('ModeExtension varies between first frame and other frames (known free-format issue in LAME 3.88)');
                $info['audio']['codec']   = 'LAME';
                $info['audio']['encoder'] = 'LAME3.88';
                $sync_pattern1 = substr($sync_pattern1, 0, 3);
                $sync_pattern2 = substr($sync_pattern2, 0, 3);
            }
        }

        if ($deep_scan) {

            $actual_frame_length_values = array ();
            $next_offset = $offset + $frame_length;
            while ($next_offset < ($info['avdataend'] - 6)) {
                fseek($fd, $next_offset - 1, SEEK_SET);
                $NextSyncPattern = fread($fd, 6);
                if ((substr($NextSyncPattern, 1, strlen($sync_pattern1)) == $sync_pattern1) || (substr($NextSyncPattern, 1, strlen($sync_pattern2)) == $sync_pattern2)) {
                    // good - found where expected
                    $actual_frame_length_values[] = $frame_length;
                } elseif ((substr($NextSyncPattern, 0, strlen($sync_pattern1)) == $sync_pattern1) || (substr($NextSyncPattern, 0, strlen($sync_pattern2)) == $sync_pattern2)) {
                    // ok - found one byte earlier than expected (last frame wasn't padded, first frame was)
                    $actual_frame_length_values[] = ($frame_length - 1);
                    $next_offset--;
                } elseif ((substr($NextSyncPattern, 2, strlen($sync_pattern1)) == $sync_pattern1) || (substr($NextSyncPattern, 2, strlen($sync_pattern2)) == $sync_pattern2)) {
                    // ok - found one byte later than expected (last frame was padded, first frame wasn't)
                    $actual_frame_length_values[] = ($frame_length + 1);
                    $next_offset++;
                } else {
                    throw new getid3_exception('Did not find expected free-format sync pattern at offset '.$next_offset);
                }
                $next_offset += $frame_length;
            }
            if (count($actual_frame_length_values) > 0) {
                $frame_length = intval(round(array_sum($actual_frame_length_values) / count($actual_frame_length_values)));
            }
        }
        return $frame_length;
    }

    

    public function getOnlyMPEGaudioInfo($fd, &$info, $avdata_offset, $bit_rate_histogram=false) {

        // looks for synch, decodes MPEG audio header
     
        fseek($fd, $avdata_offset, SEEK_SET);
        
        $sync_seek_buffer_size = min(128 * 1024, $info['avdataend'] - $avdata_offset);
        $header = fread($fd, $sync_seek_buffer_size);
        $sync_seek_buffer_size = strlen($header);
        $synch_seek_offset = 0;
        
        static $mpeg_audio_version_lookup;
        static $mpeg_audio_layer_lookup;
        static $mpeg_audio_bitrate_lookup;
        if (empty($mpeg_audio_version_lookup)) {
            $mpeg_audio_version_lookup = getid3_mp3::MPEGaudioVersionarray();
            $mpeg_audio_layer_lookup   = getid3_mp3::MPEGaudioLayerarray();
            $mpeg_audio_bitrate_lookup = getid3_mp3::MPEGaudioBitratearray();

        }

        while ($synch_seek_offset < $sync_seek_buffer_size) {
     
            if ((($avdata_offset + $synch_seek_offset)  < $info['avdataend']) && !feof($fd)) {
     
                // if a synch's not found within the first 128k bytes, then give up               
                if ($synch_seek_offset > $sync_seek_buffer_size) {
                    throw new getid3_exception('Could not find valid MPEG audio synch within the first '.round($sync_seek_buffer_size / 1024).'kB');
                } 
              
                if (feof($fd)) {
                    throw new getid3_exception('Could not find valid MPEG audio synch before end of file');
                }
            }
     
           if (($synch_seek_offset + 1) >= strlen($header)) {
                throw new getid3_exception('Could not find valid MPEG synch before end of file');
           }
     
           if (($header{$synch_seek_offset} == "\xFF") && ($header{($synch_seek_offset + 1)} > "\xE0")) { // synch detected 

                if (!isset($first_frame_info) && !isset($info['mpeg']['audio'])) {
                    $first_frame_info = $info;
                    $first_frame_avdata_offset = $avdata_offset + $synch_seek_offset;
                    if (!getid3_mp3::decodeMPEGaudioHeader($fd, $avdata_offset + $synch_seek_offset, $first_frame_info, false)) {
                        // if this is the first valid MPEG-audio frame, save it in case it's a VBR header frame and there's
                        // garbage between this frame and a valid sequence of MPEG-audio frames, to be restored below
                        unset($first_frame_info);
                    }
                }

                $dummy = $info; // only overwrite real data if valid header found
                if (getid3_mp3::decodeMPEGaudioHeader($fd, $avdata_offset + $synch_seek_offset, $dummy, true)) {
                    $info = $dummy;
                    $info['avdataoffset'] = $avdata_offset + $synch_seek_offset;
                    
                    switch (@$info['fileformat']) {
                        case '':
                        case 'mp3':
                            $info['fileformat']          = 'mp3';
                            $info['audio']['dataformat'] = 'mp3';
                            break;
                    }
                    if (isset($first_frame_info['mpeg']['audio']['bitrate_mode']) && ($first_frame_info['mpeg']['audio']['bitrate_mode'] == 'vbr')) {
                        if (!(abs($info['audio']['bitrate'] - $first_frame_info['audio']['bitrate']) <= 1)) {
                            // If there is garbage data between a valid VBR header frame and a sequence
                            // of valid MPEG-audio frames the VBR data is no longer discarded.
                            $info = $first_frame_info;
                            $info['avdataoffset']        = $first_frame_avdata_offset;
                            $info['fileformat']          = 'mp3';
                            $info['audio']['dataformat'] = 'mp3';
                            $dummy                               = $info;
                            unset($dummy['mpeg']['audio']);
                            $GarbageOffsetStart = $first_frame_avdata_offset + $first_frame_info['mpeg']['audio']['framelength'];
                            $GarbageOffsetEnd   = $avdata_offset + $synch_seek_offset;
                            if (getid3_mp3::decodeMPEGaudioHeader($fd, $GarbageOffsetEnd, $dummy, true, true)) {

                                $info = $dummy;
                                $info['avdataoffset'] = $GarbageOffsetEnd;
                                $this->getid3->warning('apparently-valid VBR header not used because could not find '.getid3_mp3::VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.'), but did find valid CBR stream starting at '.$GarbageOffsetEnd);

                            } else {

                                $this->getid3->warning('using data from VBR header even though could not find '.getid3_mp3::VALID_CHECK_FRAMES.' consecutive MPEG-audio frames immediately after VBR header (garbage data for '.($GarbageOffsetEnd - $GarbageOffsetStart).' bytes between '.$GarbageOffsetStart.' and '.$GarbageOffsetEnd.')');

                            }
                        }
                    }
                    if (isset($info['mpeg']['audio']['bitrate_mode']) && ($info['mpeg']['audio']['bitrate_mode'] == 'vbr') && !isset($info['mpeg']['audio']['VBR_method'])) {
                        // VBR file with no VBR header
                        $bit_rate_histogram = true;
                    }

                    if ($bit_rate_histogram) {

                        $info['mpeg']['audio']['stereo_distribution']  = array ('stereo'=>0, 'joint stereo'=>0, 'dual channel'=>0, 'mono'=>0);
                        $info['mpeg']['audio']['version_distribution'] = array ('1'=>0, '2'=>0, '2.5'=>0);

                        if ($info['mpeg']['audio']['version'] == '1') {
                            if ($info['mpeg']['audio']['layer'] == 3) {
                                $info['mpeg']['audio']['bitrate_distribution'] = array ('free'=>0, 32000=>0, 40000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 320000=>0);
                            } elseif ($info['mpeg']['audio']['layer'] == 2) {
                                $info['mpeg']['audio']['bitrate_distribution'] = array ('free'=>0, 32000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 320000=>0, 384000=>0);
                            } elseif ($info['mpeg']['audio']['layer'] == 1) {
                                $info['mpeg']['audio']['bitrate_distribution'] = array ('free'=>0, 32000=>0, 64000=>0, 96000=>0, 128000=>0, 160000=>0, 192000=>0, 224000=>0, 256000=>0, 288000=>0, 320000=>0, 352000=>0, 384000=>0, 416000=>0, 448000=>0);
                            }
                        } elseif ($info['mpeg']['audio']['layer'] == 1) {
                            $info['mpeg']['audio']['bitrate_distribution'] = array ('free'=>0, 32000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 144000=>0, 160000=>0, 176000=>0, 192000=>0, 224000=>0, 256000=>0);
                        } else {
                            $info['mpeg']['audio']['bitrate_distribution'] = array ('free'=>0, 8000=>0, 16000=>0, 24000=>0, 32000=>0, 40000=>0, 48000=>0, 56000=>0, 64000=>0, 80000=>0, 96000=>0, 112000=>0, 128000=>0, 144000=>0, 160000=>0);
                        }

                        $dummy = array ('avdataend' => $info['avdataend'], 'avdataoffset' => $info['avdataoffset']);
                        $synch_start_offset = $info['avdataoffset'];

                        $fast_mode = false;
                        $synch_errors_found = 0;
                        while ($this->decodeMPEGaudioHeader($fd, $synch_start_offset, $dummy, false, false, $fast_mode)) {
                            $fast_mode = true;
                            $thisframebitrate = $mpeg_audio_bitrate_lookup[$mpeg_audio_version_lookup[$dummy['mpeg']['audio']['raw']['version']]][$mpeg_audio_layer_lookup[$dummy['mpeg']['audio']['raw']['layer']]][$dummy['mpeg']['audio']['raw']['bitrate']];

                            if (empty($dummy['mpeg']['audio']['framelength'])) {
                                $synch_errors_found++;
                            } 
                            else {
                                @$info['mpeg']['audio']['bitrate_distribution'][$thisframebitrate]++;
                                @$info['mpeg']['audio']['stereo_distribution'][$dummy['mpeg']['audio']['channelmode']]++;
                                @$info['mpeg']['audio']['version_distribution'][$dummy['mpeg']['audio']['version']]++;

                                $synch_start_offset += $dummy['mpeg']['audio']['framelength'];
                            }
                        }
                        if ($synch_errors_found > 0) {
                            $this->getid3->warning('Found '.$synch_errors_found.' synch errors in histogram analysis');
                        }

                        $bit_total     = 0;
                        $frame_counter = 0;
                        foreach ($info['mpeg']['audio']['bitrate_distribution'] as $bit_rate_value => $bit_rate_count) {
                            $frame_counter += $bit_rate_count;
                            if ($bit_rate_value != 'free') {
                                $bit_total += ($bit_rate_value * $bit_rate_count);
                            }
                        }
                        if ($frame_counter == 0) {
                            throw new getid3_exception('Corrupt MP3 file: framecounter == zero');
                        }
                        $info['mpeg']['audio']['frame_count'] = $frame_counter;
                        $info['mpeg']['audio']['bitrate']     = ($bit_total / $frame_counter);

                        $info['audio']['bitrate'] = $info['mpeg']['audio']['bitrate'];


                        // Definitively set VBR vs CBR, even if the Xing/LAME/VBRI header says differently
                        $distinct_bit_rates = 0;
                        foreach ($info['mpeg']['audio']['bitrate_distribution'] as $bit_rate_value => $bit_rate_count) {
                            if ($bit_rate_count > 0) {
                                $distinct_bit_rates++;
                            }
                        }
                        if ($distinct_bit_rates > 1) {
                            $info['mpeg']['audio']['bitrate_mode'] = 'vbr';
                        } else {
                            $info['mpeg']['audio']['bitrate_mode'] = 'cbr';
                        }
                        $info['audio']['bitrate_mode'] = $info['mpeg']['audio']['bitrate_mode'];

                    }

                    break; // exit while()
                }
            }

            $synch_seek_offset++;
            if (($avdata_offset + $synch_seek_offset) >= $info['avdataend']) {
                // end of file/data

                if (empty($info['mpeg']['audio'])) {

                    throw new getid3_exception('could not find valid MPEG synch before end of file');
                }
                break;
            }

        }
        
        $info['audio']['channels']        = $info['mpeg']['audio']['channels'];
        $info['audio']['channelmode']     = $info['mpeg']['audio']['channelmode'];
        $info['audio']['sample_rate']     = $info['mpeg']['audio']['sample_rate'];
        return true;
    }



    public static function MPEGaudioVersionarray() {
        
        static $array = array ('2.5', false, '2', '1');
        return $array;
    }



    public static function MPEGaudioLayerarray() {
        
        static $array = array (false, 3, 2, 1);
        return $array;
    }



    public static function MPEGaudioBitratearray() {
        
        static $array;
        if (empty($array)) {
            $array = array (
                '1'  =>  array (1 => array ('free', 32000, 64000, 96000, 128000, 160000, 192000, 224000, 256000, 288000, 320000, 352000, 384000, 416000, 448000),
                                2 => array ('free', 32000, 48000, 56000,  64000,  80000,  96000, 112000, 128000, 160000, 192000, 224000, 256000, 320000, 384000),
                                3 => array ('free', 32000, 40000, 48000,  56000,  64000,  80000,  96000, 112000, 128000, 160000, 192000, 224000, 256000, 320000)
                               ),

                '2'  =>  array (1 => array ('free', 32000, 48000, 56000,  64000,  80000,  96000, 112000, 128000, 144000, 160000, 176000, 192000, 224000, 256000),
                                2 => array ('free',  8000, 16000, 24000,  32000,  40000,  48000,  56000,  64000,  80000,  96000, 112000, 128000, 144000, 160000),
                               )
            );
            $array['2'][3] = $array['2'][2];
            $array['2.5']  = $array['2'];
        }
        return $array;
    }



    public static function MPEGaudioFrequencyarray() {
        
        static $array = array (
                '1'   => array (44100, 48000, 32000),
                '2'   => array (22050, 24000, 16000),
                '2.5' => array (11025, 12000,  8000)
        );
        return $array;
    }



    public static function MPEGaudioChannelModearray() {
        
        static $array = array ('stereo', 'joint stereo', 'dual channel', 'mono');
        return $array;
    }



    public static function MPEGaudioModeExtensionarray() {
        
        static $array = array (
                1 => array ('4-31', '8-31', '12-31', '16-31'),
                2 => array ('4-31', '8-31', '12-31', '16-31'),
                3 => array ('', 'IS', 'MS', 'IS+MS')
        );
        return $array;
    }



    public static function MPEGaudioEmphasisarray() {
        
        static $array = array ('none', '50/15ms', false, 'CCIT J.17');
        return $array;
    }



    public static function MPEGaudioHeaderBytesValid($head4, $allow_bitrate_15=false) {
        
        return getid3_mp3::MPEGaudioHeaderValid(getid3_mp3::MPEGaudioHeaderDecode($head4), false, $allow_bitrate_15);
    }



    public static function MPEGaudioHeaderValid($raw_array, $echo_errors=false, $allow_bitrate_15=false) {
        
        if (($raw_array['synch'] & 0x0FFE) != 0x0FFE) {
            return false;
        }

        static $mpeg_audio_version_lookup;
        static $mpeg_audio_layer_lookup;
        static $mpeg_audio_bitrate_lookup;
        static $mpeg_audio_frequency_lookup;
        static $mpeg_audio_channel_mode_lookup;
        static $mpeg_audio_mode_extension_lookup;
        static $mpeg_audio_emphasis_lookup;
        if (empty($mpeg_audio_version_lookup)) {
            $mpeg_audio_version_lookup        = getid3_mp3::MPEGaudioVersionarray();
            $mpeg_audio_layer_lookup          = getid3_mp3::MPEGaudioLayerarray();
            $mpeg_audio_bitrate_lookup        = getid3_mp3::MPEGaudioBitratearray();
            $mpeg_audio_frequency_lookup      = getid3_mp3::MPEGaudioFrequencyarray();
            $mpeg_audio_channel_mode_lookup   = getid3_mp3::MPEGaudioChannelModearray();
            $mpeg_audio_mode_extension_lookup = getid3_mp3::MPEGaudioModeExtensionarray();
            $mpeg_audio_emphasis_lookup       = getid3_mp3::MPEGaudioEmphasisarray();
        }

        if (isset($mpeg_audio_version_lookup[$raw_array['version']])) {
            $decodedVersion = $mpeg_audio_version_lookup[$raw_array['version']];
        } else {
            echo ($echo_errors ? "\n".'invalid Version ('.$raw_array['version'].')' : '');
            return false;
        }
        if (isset($mpeg_audio_layer_lookup[$raw_array['layer']])) {
            $decodedLayer = $mpeg_audio_layer_lookup[$raw_array['layer']];
        } else {
            echo ($echo_errors ? "\n".'invalid Layer ('.$raw_array['layer'].')' : '');
            return false;
        }
        if (!isset($mpeg_audio_bitrate_lookup[$decodedVersion][$decodedLayer][$raw_array['bitrate']])) {
            echo ($echo_errors ? "\n".'invalid Bitrate ('.$raw_array['bitrate'].')' : '');
            if ($raw_array['bitrate'] == 15) {
                // known issue in LAME 3.90 - 3.93.1 where free-format has bitrate ID of 15 instead of 0
                // let it go through here otherwise file will not be identified
                if (!$allow_bitrate_15) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if (!isset($mpeg_audio_frequency_lookup[$decodedVersion][$raw_array['sample_rate']])) {
            echo ($echo_errors ? "\n".'invalid Frequency ('.$raw_array['sample_rate'].')' : '');
            return false;
        }
        if (!isset($mpeg_audio_channel_mode_lookup[$raw_array['channelmode']])) {
            echo ($echo_errors ? "\n".'invalid ChannelMode ('.$raw_array['channelmode'].')' : '');
            return false;
        }
        if (!isset($mpeg_audio_mode_extension_lookup[$decodedLayer][$raw_array['modeextension']])) {
            echo ($echo_errors ? "\n".'invalid Mode Extension ('.$raw_array['modeextension'].')' : '');
            return false;
        }
        if (!isset($mpeg_audio_emphasis_lookup[$raw_array['emphasis']])) {
            echo ($echo_errors ? "\n".'invalid Emphasis ('.$raw_array['emphasis'].')' : '');
            return false;
        }
        // These are just either set or not set, you can't mess that up :)
        // $raw_array['protection'];
        // $raw_array['padding'];
        // $raw_array['private'];
        // $raw_array['copyright'];
        // $raw_array['original'];

        return true;
    }



    public static function MPEGaudioHeaderDecode($header_four_bytes) {
        // AAAA AAAA  AAAB BCCD  EEEE FFGH  IIJJ KLMM
        // A - Frame sync (all bits set)
        // B - MPEG Audio version ID
        // C - Layer description
        // D - Protection bit
        // E - Bitrate index
        // F - Sampling rate frequency index
        // G - Padding bit
        // H - Private bit
        // I - Channel Mode
        // J - Mode extension (Only if Joint stereo)
        // K - Copyright
        // L - Original
        // M - Emphasis

        if (strlen($header_four_bytes) != 4) {
            return false;
        }

        $mpeg_raw_header['synch']         = (getid3_lib::BigEndian2Int(substr($header_four_bytes, 0, 2)) & 0xFFE0) >> 4;
        $mpeg_raw_header['version']       = (ord($header_four_bytes{1}) & 0x18) >> 3; //    BB
        $mpeg_raw_header['layer']         = (ord($header_four_bytes{1}) & 0x06) >> 1; //      CC
        $mpeg_raw_header['protection']    = (ord($header_four_bytes{1}) & 0x01);      //        D
        $mpeg_raw_header['bitrate']       = (ord($header_four_bytes{2}) & 0xF0) >> 4; // EEEE
        $mpeg_raw_header['sample_rate']   = (ord($header_four_bytes{2}) & 0x0C) >> 2; //     FF
        $mpeg_raw_header['padding']       = (ord($header_four_bytes{2}) & 0x02) >> 1; //       G
        $mpeg_raw_header['private']       = (ord($header_four_bytes{2}) & 0x01);      //        H
        $mpeg_raw_header['channelmode']   = (ord($header_four_bytes{3}) & 0xC0) >> 6; // II
        $mpeg_raw_header['modeextension'] = (ord($header_four_bytes{3}) & 0x30) >> 4; //   JJ
        $mpeg_raw_header['copyright']     = (ord($header_four_bytes{3}) & 0x08) >> 3; //     K
        $mpeg_raw_header['original']      = (ord($header_four_bytes{3}) & 0x04) >> 2; //      L
        $mpeg_raw_header['emphasis']      = (ord($header_four_bytes{3}) & 0x03);      //       MM

        return $mpeg_raw_header;
    }



    public static function MPEGaudioFrameLength(&$bit_rate, &$version, &$layer, $padding, &$sample_rate) {
        
        if (!isset($cache[$bit_rate][$version][$layer][$padding][$sample_rate])) {
            $cache[$bit_rate][$version][$layer][$padding][$sample_rate] = false;
            if ($bit_rate != 'free') {

                if ($version == '1') {

                    if ($layer == '1') {

                        // For Layer I slot is 32 bits long
                        $frame_length_coefficient = 48;
                        $slot_length = 4;

                    } else { // Layer 2 / 3

                        // for Layer 2 and Layer 3 slot is 8 bits long.
                        $frame_length_coefficient = 144;
                        $slot_length = 1;

                    }

                } else { // MPEG-2 / MPEG-2.5

                    if ($layer == '1') {

                        // For Layer I slot is 32 bits long
                        $frame_length_coefficient = 24;
                        $slot_length = 4;

                    } elseif ($layer == '2') {

                        // for Layer 2 and Layer 3 slot is 8 bits long.
                        $frame_length_coefficient = 144;
                        $slot_length = 1;

                    } else { // layer 3

                        // for Layer 2 and Layer 3 slot is 8 bits long.
                        $frame_length_coefficient = 72;
                        $slot_length = 1;

                    }

                }

                // FrameLengthInBytes = ((Coefficient * BitRate) / SampleRate) + Padding
                if ($sample_rate > 0) {
                    $new_frame_length  = ($frame_length_coefficient * $bit_rate) / $sample_rate;
                    $new_frame_length  = floor($new_frame_length / $slot_length) * $slot_length; // round to next-lower multiple of SlotLength (1 byte for Layer 2/3, 4 bytes for Layer I)
                    if ($padding) {
                        $new_frame_length += $slot_length;
                    }
                    $cache[$bit_rate][$version][$layer][$padding][$sample_rate] = (int) $new_frame_length;
                }
            }
        }
        return $cache[$bit_rate][$version][$layer][$padding][$sample_rate];
    }



    public static function ClosestStandardMP3Bitrate($bit_rate) {
        
        static $standard_bit_rates = array (320000, 256000, 224000, 192000, 160000, 128000, 112000, 96000, 80000, 64000, 56000, 48000, 40000, 32000, 24000, 16000, 8000);
        static $bit_rate_table = array (0=>'-');
        $round_bit_rate = intval(round($bit_rate, -3));
        if (!isset($bit_rate_table[$round_bit_rate])) {
            if ($round_bit_rate > 320000) {
                $bit_rate_table[$round_bit_rate] = round($bit_rate, -4);
            } else {
                $last_bit_rate = 320000;
                foreach ($standard_bit_rates as $standard_bit_rate) {
                    $bit_rate_table[$round_bit_rate] = $standard_bit_rate;
                    if ($round_bit_rate >= $standard_bit_rate - (($last_bit_rate - $standard_bit_rate) / 2)) {
                        break;
                    }
                    $last_bit_rate = $standard_bit_rate;
                }
            }
        }
        return $bit_rate_table[$round_bit_rate];
    }



    public static function XingVBRidOffset($version, $channel_mode) {
        
        static $lookup = array (
                '1'   => array ('mono'          => 0x15, // 4 + 17 = 21
                                'stereo'        => 0x24, // 4 + 32 = 36
                                'joint stereo'  => 0x24,
                                'dual channel'  => 0x24
                               ),

                '2'   => array ('mono'          => 0x0D, // 4 +  9 = 13
                                'stereo'        => 0x15, // 4 + 17 = 21
                                'joint stereo'  => 0x15,
                                'dual channel'  => 0x15
                               ),

                '2.5' => array ('mono'          => 0x15,
                                'stereo'        => 0x15,
                                'joint stereo'  => 0x15,
                                'dual channel'  => 0x15
                               )
        );
        
        return $lookup[$version][$channel_mode];
    }



    public static function LAMEvbrMethodLookup($vbr_method_id) {
        
        static $lookup = array (
            0x00 => 'unknown',
            0x01 => 'cbr',
            0x02 => 'abr',
            0x03 => 'vbr-old / vbr-rh',
            0x04 => 'vbr-new / vbr-mtrh',
            0x05 => 'vbr-mt',
            0x06 => 'Full VBR Method 4',
            0x08 => 'constant bitrate 2 pass',
            0x09 => 'abr 2 pass',
            0x0F => 'reserved'
        );
        return (isset($lookup[$vbr_method_id]) ? $lookup[$vbr_method_id] : '');
    }



    public static function LAMEmiscStereoModeLookup($stereo_mode_id) {
    
        static $lookup = array (
            0 => 'mono',
            1 => 'stereo',
            2 => 'dual mono',
            3 => 'joint stereo',
            4 => 'forced stereo',
            5 => 'auto',
            6 => 'intensity stereo',
            7 => 'other'
        );
        return (isset($lookup[$stereo_mode_id]) ? $lookup[$stereo_mode_id] : '');
    }



    public static function LAMEmiscSourceSampleFrequencyLookup($source_sample_frequency_id) {
        
        static $lookup = array (
            0 => '<= 32 kHz',
            1 => '44.1 kHz',
            2 => '48 kHz',
            3 => '> 48kHz'
        );
        return (isset($lookup[$source_sample_frequency_id]) ? $lookup[$source_sample_frequency_id] : '');
    }



    public static function LAMEsurroundInfoLookup($surround_info_id) {
        
        static $lookup = array (
            0 => 'no surround info',
            1 => 'DPL encoding',
            2 => 'DPL2 encoding',
            3 => 'Ambisonic encoding'
        );
        return (isset($lookup[$surround_info_id]) ? $lookup[$surround_info_id] : 'reserved');
    }



    public static function LAMEpresetUsedLookup($lame_tag) {
        
        if ($lame_tag['preset_used_id'] == 0) {
            // no preset used (LAME >=3.93)
            // no preset recorded (LAME <3.93)
            return '';
        }
        
        $lame_preset_used_lookup = array ();
        
        for ($i = 8; $i <= 320; $i++) {
            switch ($lame_tag['vbr_method']) {
                case 'cbr':
                    $lame_preset_used_lookup[$i] = '--alt-preset '.$lame_tag['vbr_method'].' '.$i;
                    break;
                case 'abr':
                default: // other VBR modes shouldn't be here(?)
                    $lame_preset_used_lookup[$i] = '--alt-preset '.$i;
                    break;
            }
        }

        // named old-style presets (studio, phone, voice, etc) are handled in GuessEncoderOptions()

        // named alt-presets
        $lame_preset_used_lookup[1000] = '--r3mix';
        $lame_preset_used_lookup[1001] = '--alt-preset standard';
        $lame_preset_used_lookup[1002] = '--alt-preset extreme';
        $lame_preset_used_lookup[1003] = '--alt-preset insane';
        $lame_preset_used_lookup[1004] = '--alt-preset fast standard';
        $lame_preset_used_lookup[1005] = '--alt-preset fast extreme';
        $lame_preset_used_lookup[1006] = '--alt-preset medium';
        $lame_preset_used_lookup[1007] = '--alt-preset fast medium';

        // LAME 3.94 additions/changes
        $lame_preset_used_lookup[1010] = '--preset portable';                                                            // 3.94a15 Oct 21 2003
        $lame_preset_used_lookup[1015] = '--preset radio';                                                               // 3.94a15 Oct 21 2003

        $lame_preset_used_lookup[320]  = '--preset insane';                                                              // 3.94a15 Nov 12 2003
        $lame_preset_used_lookup[410]  = '-V9';
        $lame_preset_used_lookup[420]  = '-V8';
        $lame_preset_used_lookup[430]  = '--preset radio';                                                               // 3.94a15 Nov 12 2003
        $lame_preset_used_lookup[440]  = '-V6';
        $lame_preset_used_lookup[450]  = '--preset '.(($lame_tag['raw']['vbr_method'] == 4) ? 'fast ' : '').'portable';  // 3.94a15 Nov 12 2003
        $lame_preset_used_lookup[460]  = '--preset '.(($lame_tag['raw']['vbr_method'] == 4) ? 'fast ' : '').'medium';    // 3.94a15 Nov 12 2003
        $lame_preset_used_lookup[470]  = '--r3mix';                                                                      // 3.94b1  Dec 18 2003
        $lame_preset_used_lookup[480]  = '--preset '.(($lame_tag['raw']['vbr_method'] == 4) ? 'fast ' : '').'standard';  // 3.94a15 Nov 12 2003
        $lame_preset_used_lookup[490]  = '-V1';
        $lame_preset_used_lookup[500]  = '--preset '.(($lame_tag['raw']['vbr_method'] == 4) ? 'fast ' : '').'extreme';   // 3.94a15 Nov 12 2003
        
        return (isset($lame_preset_used_lookup[$lame_tag['preset_used_id']]) ? $lame_preset_used_lookup[$lame_tag['preset_used_id']] : 'new/unknown preset: '.$lame_tag['preset_used_id'].' - report to info@getid3.org');
    }
	
	
}

?>