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
// | module.audio.aac_adif.php                                            |
// | Module for analyzing AAC files with ADIF header.                     |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.aac_adif.php,v 1.3 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_aac_adif extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;    

        // http://faac.sourceforge.net/wiki/index.php?page=ADIF
        // http://libmpeg.org/mpeg4/doc/w2203tfs.pdf
        // adif_header() {
        //     adif_id                                32
        //     copyright_id_present                    1
        //     if( copyright_id_present )
        //         copyright_id                       72
        //     original_copy                           1
        //     home                                    1
        //     bitstream_type                          1
        //     bitrate                                23
        //     num_program_config_elements             4
        //     for (i = 0; i < num_program_config_elements + 1; i++ ) {
        //         if( bitstream_type == '0' )
        //             adif_buffer_fullness           20
        //         program_config_element()
        //     }
        // }
        

        $getid3->info['fileformat']          = 'aac';
        $getid3->info['audio']['dataformat'] = 'aac';
        $getid3->info['audio']['lossless']   = false;
        
        $getid3->info['aac']['header'] = array () ;
        $info_aac        = &$getid3->info['aac'];
        $info_aac_header = & $info_aac['header'];

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $aac_header_bitstream = getid3_lib::BigEndian2Bin(fread($getid3->fp, 1024));
        
        $info_aac['header_type']            = 'ADIF';
        $info_aac_header['mpeg_version'] = 4;
        $bit_offset = 32;

        $info_aac_header['copyright'] = $aac_header_bitstream{$bit_offset++} == '1';
        if ($info_aac_header['copyright']) {
            $info_aac_header['copyright_id'] = getid3_aac_adif::Bin2String(substr($aac_header_bitstream, $bit_offset, 72));
            $bit_offset += 72;
        }
        
        $info_aac_header['original_copy'] = $aac_header_bitstream{$bit_offset++} == '1';
        $info_aac_header['home']          = $aac_header_bitstream{$bit_offset++} == '1';
        $info_aac_header['is_vbr']        = $aac_header_bitstream{$bit_offset++} == '1';

        if ($info_aac_header['is_vbr']) {
            $getid3->info['audio']['bitrate_mode'] = 'vbr';
            $info_aac_header['bitrate_max']     = bindec(substr($aac_header_bitstream, $bit_offset, 23));
            $bit_offset += 23;
        } 
        else {
            $getid3->info['audio']['bitrate_mode'] = 'cbr';
            $info_aac_header['bitrate']         = bindec(substr($aac_header_bitstream, $bit_offset, 23));
            $bit_offset += 23;
            $getid3->info['audio']['bitrate']      = $info_aac_header['bitrate'];
        }
        
        $info_aac_header['num_program_configs'] = 1 + bindec(substr($aac_header_bitstream, $bit_offset, 4));
        $bit_offset += 4;

        for ($i = 0; $i < $info_aac_header['num_program_configs']; $i++) {

            // http://www.audiocoding.com/wiki/index.php?page=program_config_element

            // buffer_fullness                       20

            // element_instance_tag                   4
            // object_type                            2
            // sampling_frequency_index               4
            // num_front_channel_elements             4
            // num_side_channel_elements              4
            // num_back_channel_elements              4
            // num_lfe_channel_elements               2
            // num_assoc_data_elements                3
            // num_valid_cc_elements                  4
            // mono_mixdown_present                   1
            // mono_mixdown_element_number            4   if mono_mixdown_present == 1
            // stereo_mixdown_present                 1
            // stereo_mixdown_element_number          4   if stereo_mixdown_present == 1
            // matrix_mixdown_idx_present             1
            // matrix_mixdown_idx                     2   if matrix_mixdown_idx_present == 1
            // pseudo_surround_enable                 1   if matrix_mixdown_idx_present == 1
            // for (i = 0; i < num_front_channel_elements; i++) {
            //     front_element_is_cpe[i]            1
            //     front_element_tag_select[i]        4
            // }
            // for (i = 0; i < num_side_channel_elements; i++) {
            //     side_element_is_cpe[i]             1
            //     side_element_tag_select[i]         4
            // }
            // for (i = 0; i < num_back_channel_elements; i++) {
            //     back_element_is_cpe[i]             1
            //     back_element_tag_select[i]         4
            // }
            // for (i = 0; i < num_lfe_channel_elements; i++) {
            //     lfe_element_tag_select[i]          4
            // }
            // for (i = 0; i < num_assoc_data_elements; i++) {
            //     assoc_data_element_tag_select[i]   4
            // }
            // for (i = 0; i < num_valid_cc_elements; i++) {
            //     cc_element_is_ind_sw[i]            1
            //     valid_cc_element_tag_select[i]     4
            // }
            // byte_alignment()                       VAR
            // comment_field_bytes                    8
            // for (i = 0; i < comment_field_bytes; i++) {
            //     comment_field_data[i]              8
            // }
            
            $info_aac['program_configs'][$i] = array ();
            $info_aac_program_configs_i = &$info_aac['program_configs'][$i];

            if (!$info_aac_header['is_vbr']) {
                $info_aac_program_configs_i['buffer_fullness']        = bindec(substr($aac_header_bitstream, $bit_offset, 20));
                $bit_offset += 20;
            }
                
            $info_aac_program_configs_i['element_instance_tag']       = bindec(substr($aac_header_bitstream, $bit_offset,      4));
            $info_aac_program_configs_i['object_type']                = bindec(substr($aac_header_bitstream, $bit_offset +  4, 2));
            $info_aac_program_configs_i['sampling_frequency_index']   = bindec(substr($aac_header_bitstream, $bit_offset +  6, 4));
            $info_aac_program_configs_i['num_front_channel_elements'] = bindec(substr($aac_header_bitstream, $bit_offset + 10, 4));
            $info_aac_program_configs_i['num_side_channel_elements']  = bindec(substr($aac_header_bitstream, $bit_offset + 14, 4));
            $info_aac_program_configs_i['num_back_channel_elements']  = bindec(substr($aac_header_bitstream, $bit_offset + 18, 4));
            $info_aac_program_configs_i['num_lfe_channel_elements']   = bindec(substr($aac_header_bitstream, $bit_offset + 22, 2));
            $info_aac_program_configs_i['num_assoc_data_elements']    = bindec(substr($aac_header_bitstream, $bit_offset + 24, 3));
            $info_aac_program_configs_i['num_valid_cc_elements']      = bindec(substr($aac_header_bitstream, $bit_offset + 27, 4));
            $bit_offset += 31;
            
            $info_aac_program_configs_i['mono_mixdown_present'] = $aac_header_bitstream{$bit_offset++} == 1;
            if ($info_aac_program_configs_i['mono_mixdown_present']) {
                $info_aac_program_configs_i['mono_mixdown_element_number'] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            
            $info_aac_program_configs_i['stereo_mixdown_present'] = $aac_header_bitstream{$bit_offset++} == 1;
            if ($info_aac_program_configs_i['stereo_mixdown_present']) {
                $info_aac_program_configs_i['stereo_mixdown_element_number'] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            
            $info_aac_program_configs_i['matrix_mixdown_idx_present'] = $aac_header_bitstream{$bit_offset++} == 1;
            if ($info_aac_program_configs_i['matrix_mixdown_idx_present']) {
                $info_aac_program_configs_i['matrix_mixdown_idx']     = bindec(substr($aac_header_bitstream, $bit_offset, 2));
                $bit_offset += 2;
                $info_aac_program_configs_i['pseudo_surround_enable'] = $aac_header_bitstream{$bit_offset++} == 1;
            }
            
            for ($j = 0; $j < $info_aac_program_configs_i['num_front_channel_elements']; $j++) {
                $info_aac_program_configs_i['front_element_is_cpe'][$j]     = $aac_header_bitstream{$bit_offset++} == 1;
                $info_aac_program_configs_i['front_element_tag_select'][$j] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            for ($j = 0; $j < $info_aac_program_configs_i['num_side_channel_elements']; $j++) {
                $info_aac_program_configs_i['side_element_is_cpe'][$j]     = $aac_header_bitstream{$bit_offset++} == 1;
                $info_aac_program_configs_i['side_element_tag_select'][$j] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            for ($j = 0; $j < $info_aac_program_configs_i['num_back_channel_elements']; $j++) {
                $info_aac_program_configs_i['back_element_is_cpe'][$j]     = $aac_header_bitstream{$bit_offset++} == 1;
                $info_aac_program_configs_i['back_element_tag_select'][$j] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            for ($j = 0; $j < $info_aac_program_configs_i['num_lfe_channel_elements']; $j++) {
                $info_aac_program_configs_i['lfe_element_tag_select'][$j] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            for ($j = 0; $j < $info_aac_program_configs_i['num_assoc_data_elements']; $j++) {
                $info_aac_program_configs_i['assoc_data_element_tag_select'][$j] = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }
            for ($j = 0; $j < $info_aac_program_configs_i['num_valid_cc_elements']; $j++) {
                $info_aac_program_configs_i['cc_element_is_ind_sw'][$j]          = $aac_header_bitstream{$bit_offset++} == 1;
                $info_aac_program_configs_i['valid_cc_element_tag_select'][$j]   = bindec(substr($aac_header_bitstream, $bit_offset, 4));
                $bit_offset += 4;
            }

            $bit_offset = ceil($bit_offset / 8) * 8;

            $info_aac_program_configs_i['comment_field_bytes'] = bindec(substr($aac_header_bitstream, $bit_offset, 8));
            $bit_offset += 8;
            
            $info_aac_program_configs_i['comment_field'] = getid3_aac_adif::Bin2String(substr($aac_header_bitstream, $bit_offset, 8 * $info_aac_program_configs_i['comment_field_bytes']));
            $bit_offset += 8 * $info_aac_program_configs_i['comment_field_bytes'];

            $info_aac_header['profile_text']                  = getid3_aac_adif::AACprofileLookup($info_aac_program_configs_i['object_type'], $info_aac_header['mpeg_version']);
            $info_aac_program_configs_i['sampling_frequency'] = $getid3->info['audio']['sample_rate'] = getid3_aac_adif::AACsampleRateLookup($info_aac_program_configs_i['sampling_frequency_index']);
            $getid3->info['audio']['channels']                = getid3_aac_adif::AACchannelCountCalculate($info_aac_program_configs_i);
            
            if ($info_aac_program_configs_i['comment_field']) {
                $info_aac['comments'][] = $info_aac_program_configs_i['comment_field'];
            }
        }
        
        $getid3->info['playtime_seconds'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['audio']['bitrate'];
        $getid3->info['audio']['encoder_options'] = $info_aac['header_type'].' '.$info_aac_header['profile_text'];

        return true;
    }



    public static function Bin2String($bin_string) {
        // return 'hi' for input of '0110100001101001'
        $string = '';
        $bin_string_reversed = strrev($bin_string);
        for ($i = 0; $i < strlen($bin_string_reversed); $i += 8) {
            $string = chr(bindec(strrev(substr($bin_string_reversed, $i, 8)))).$string;
        }
        return $string;
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



    public static function AACchannelCountCalculate($program_configs) {
        
        $channels = 0;
        
        foreach (array ('front', 'side', 'back') as $placement) {
            for ($i = 0; $i < $program_configs['num_'.$placement.'_channel_elements']; $i++) {
                
                // Each element is channel pair (CPE = Channel Pair Element)
                $channels += 1 + ($program_configs[$placement.'_element_is_cpe'][$i] ? 1 : 0);
            }
        }
        
        return $channels + $program_configs['num_lfe_channel_elements'];
    }

}


?>