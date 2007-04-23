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
// | module.audio.optimfrog.php                                           |
// | Module for analyzing OptimFROG Audio files                           |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.optimfrog.php,v 1.3 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_optimfrog extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');
        
        $getid3->info['audio']['dataformat']   = 'ofr';
        $getid3->info['audio']['bitrate_mode'] = 'vbr';
        $getid3->info['audio']['lossless']     = true;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $ofr_header = fread($getid3->fp, 8);
        
        if (substr($ofr_header, 0, 5) == '*RIFF') {
            return $this->ParseOptimFROGheader42($getid3->fp, $getid3->info);

        } elseif (substr($ofr_header, 0, 3) == 'OFR') {
            return $this->ParseOptimFROGheader45($getid3->fp, $getid3->info);
        }
        
        throw new getid3_exception('Expecting "*RIFF" or "OFR " at offset '.$getid3->info['avdataoffset'].', found "'.$ofr_header.'"');
    }



    private function ParseOptimFROGheader42() {
        
        $getid3 = $this->getid3;
        
        // for fileformat of v4.21 and older

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        
        $ofr_header_data = fread($getid3->fp, 45);
        $getid3->info['avdataoffset'] = 45;

        $ofr_encoder_version_raw   = getid3_lib::LittleEndian2Int(substr($ofr_header_data, 0, 1));
        $ofr_encoder_version_major = floor($ofr_encoder_version_raw / 10);
        $ofr_encoder_version_minor = $ofr_encoder_version_raw - ($ofr_encoder_version_major * 10);
        $riff_data                 = substr($ofr_header_data, 1, 44);
        $origna_riff_header_size   = getid3_lib::LittleEndian2Int(substr($riff_data,  4, 4)) +  8;
        $origna_riff_data_size     = getid3_lib::LittleEndian2Int(substr($riff_data, 40, 4)) + 44;

        if ($origna_riff_header_size > $origna_riff_data_size) {
            $getid3->info['avdataend'] -= ($origna_riff_header_size - $origna_riff_data_size);
            fseek($getid3->fp, $getid3->info['avdataend'], SEEK_SET);
            $riff_data .= fread($getid3->fp, $origna_riff_header_size - $origna_riff_data_size);
        }

        // move the data chunk after all other chunks (if any)
        // so that the RIFF parser doesn't see EOF when trying
        // to skip over the data chunk
        
        $riff_data = substr($riff_data, 0, 36).substr($riff_data, 44).substr($riff_data, 36, 8);
        
        // Save audio info key
        $saved_info_audio = $getid3->info['audio'];

        // Instantiate riff module and analyze string
        $riff = new getid3_riff($getid3);
        $riff->AnalyzeString($riff_data);
        
        // Restore info key
        $getid3->info['audio'] = $saved_info_audio;
        
        $getid3->info['audio']['encoder']         = 'OptimFROG '.$ofr_encoder_version_major.'.'.$ofr_encoder_version_minor;
        $getid3->info['audio']['channels']        = $getid3->info['riff']['audio'][0]['channels'];
        $getid3->info['audio']['sample_rate']     = $getid3->info['riff']['audio'][0]['sample_rate'];
        $getid3->info['audio']['bits_per_sample'] = $getid3->info['riff']['audio'][0]['bits_per_sample'];
        $getid3->info['playtime_seconds']         = $origna_riff_data_size / ($getid3->info['audio']['channels'] * $getid3->info['audio']['sample_rate'] * ($getid3->info['audio']['bits_per_sample'] / 8));
        $getid3->info['audio']['bitrate']         = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
        
        $getid3->info['fileformat'] = 'ofr';

        return true;
    }



    private function ParseOptimFROGheader45() {
        
        $getid3 = $this->getid3;
        
        // for fileformat of v4.50a and higher

        $riff_data = '';
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        
        while (!feof($getid3->fp) && (ftell($getid3->fp) < $getid3->info['avdataend'])) {
        
            $block_offset = ftell($getid3->fp);
            $block_data   = fread($getid3->fp, 8);
            $offset       = 8;
            $block_name   =                  substr($block_data, 0, 4);
            $block_size   = getid3_lib::LittleEndian2Int(substr($block_data, 4, 4));

            if ($block_name == 'OFRX') {
                $block_name = 'OFR ';
            }
            if (!isset($getid3->info['ofr'][$block_name])) {
                $getid3->info['ofr'][$block_name] = array ();
            }
            $info_ofr_this_block = &$getid3->info['ofr'][$block_name];

            switch ($block_name) {
                case 'OFR ':

                    // shortcut
                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    $getid3->info['audio']['encoder'] = 'OptimFROG 4.50 alpha';
                    switch ($block_size) {
                        case 12:
                        case 15:
                            // good
                            break;

                        default:
                            $getid3->warning('"'.$block_name.'" contains more data than expected (expected 12 or 15 bytes, found '.$block_size.' bytes)');
                            break;
                    }
                    $block_data .= fread($getid3->fp, $block_size);

                    $info_ofr_this_block['total_samples']      = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 6));
                    $offset += 6;
                    
                    $info_ofr_this_block['raw']['sample_type'] = getid3_lib::LittleEndian2Int($block_data{$offset++});
                    $info_ofr_this_block['sample_type']        = $this->OptimFROGsampleTypeLookup($info_ofr_this_block['raw']['sample_type']);
                    
                    $info_ofr_this_block['channel_config']     = getid3_lib::LittleEndian2Int($block_data{$offset++});
                    $info_ofr_this_block['channels']           = $info_ofr_this_block['channel_config'];

                    $info_ofr_this_block['sample_rate']        = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 4));
                    $offset += 4;

                    if ($block_size > 12) {

                        // OFR 4.504b or higher
                        $info_ofr_this_block['channels']           = $this->OptimFROGchannelConfigNumChannelsLookup($info_ofr_this_block['channel_config']);
                        $info_ofr_this_block['raw']['encoder_id']  = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 2));
                        $info_ofr_this_block['encoder']            = $this->OptimFROGencoderNameLookup($info_ofr_this_block['raw']['encoder_id']);
                        $offset += 2;

                        $info_ofr_this_block['raw']['compression'] = getid3_lib::LittleEndian2Int($block_data{$offset++});
                        $info_ofr_this_block['compression']        = $this->OptimFROGcompressionLookup($info_ofr_this_block['raw']['compression']);
                        $info_ofr_this_block['speedup']            = $this->OptimFROGspeedupLookup($info_ofr_this_block['raw']['compression']);

                        $getid3->info['audio']['encoder']         = 'OptimFROG '.$info_ofr_this_block['encoder'];
                        $getid3->info['audio']['encoder_options'] = '--mode '.$info_ofr_this_block['compression'];

                        if ((($info_ofr_this_block['raw']['encoder_id'] & 0xF0) >> 4) == 7) { // v4.507
                            if (preg_match('/\.ofs$/i', $getid3->filename)) {
                                // OptimFROG DualStream format is lossy, but as of v4.507 there is no way to tell the difference
                                // between lossless and lossy other than the file extension.
                                $getid3->info['audio']['dataformat']   = 'ofs';
                                $getid3->info['audio']['lossless']     = true;
                            }
                        }
                    }

                    $getid3->info['audio']['channels']        = $info_ofr_this_block['channels'];
                    $getid3->info['audio']['sample_rate']     = $info_ofr_this_block['sample_rate'];
                    $getid3->info['audio']['bits_per_sample'] = $this->OptimFROGbitsPerSampleTypeLookup($info_ofr_this_block['raw']['sample_type']);
                    break;


                case 'COMP':
                    // unlike other block types, there CAN be multiple COMP blocks

                    $comp_data['offset'] = $block_offset;
                    $comp_data['size']   = $block_size;

                    if ($getid3->info['avdataoffset'] == 0) {
                        $getid3->info['avdataoffset'] = $block_offset;
                    }

                    // Only interested in first 14 bytes (only first 12 needed for v4.50 alpha), not actual audio data
                    $block_data .= fread($getid3->fp, 14);
                    fseek($getid3->fp, $block_size - 14, SEEK_CUR);

                    $comp_data['crc_32']                       = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 4));
                    $offset += 4;
                    
                    $comp_data['sample_count']                 = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 4));
                    $offset += 4;
                    
                    $comp_data['raw']['sample_type']           = getid3_lib::LittleEndian2Int($block_data{$offset++});
                    $comp_data['sample_type']                  = $this->OptimFROGsampleTypeLookup($comp_data['raw']['sample_type']);
                    
                    $comp_data['raw']['channel_configuration'] = getid3_lib::LittleEndian2Int($block_data{$offset++});
                    $comp_data['channel_configuration']        = $this->OptimFROGchannelConfigurationLookup($comp_data['raw']['channel_configuration']);

                    $comp_data['raw']['algorithm_id']          = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 2));
                    $offset += 2;

                    if ($getid3->info['ofr']['OFR ']['size'] > 12) {

                        // OFR 4.504b or higher
                        $comp_data['raw']['encoder_id']        = getid3_lib::LittleEndian2Int(substr($block_data, $offset, 2));
                        $comp_data['encoder']                  = $this->OptimFROGencoderNameLookup($comp_data['raw']['encoder_id']);
                        $offset += 2;
                    }

                    if ($comp_data['crc_32'] == 0x454E4F4E) {
                        // ASCII value of 'NONE' - placeholder value in v4.50a
                        $comp_data['crc_32'] = false;
                    }

                    $info_ofr_this_block[] = $comp_data;
                    break;

                case 'HEAD':
                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    $riff_data .= fread($getid3->fp, $block_size);
                    break;

                case 'TAIL':
                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    if ($block_size > 0) {
                        $riff_data .= fread($getid3->fp, $block_size);
                    }
                    break;

                case 'RECV':
                    // block contains no useful meta data - simply note and skip

                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    fseek($getid3->fp, $block_size, SEEK_CUR);
                    break;


                case 'APET':
                    // APEtag v2

                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;
                    $getid3->warning('APEtag processing inside OptimFROG not supported in this version ('.GETID3_VERSION.') of getID3()');

                    fseek($getid3->fp, $block_size, SEEK_CUR);
                    break;


                case 'MD5 ':
                    // APEtag v2

                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    if ($block_size == 16) {

                        $info_ofr_this_block['md5_binary'] = fread($getid3->fp, $block_size);
                        $info_ofr_this_block['md5_string'] = getid3_lib::PrintHexBytes($info_ofr_this_block['md5_binary'], true, false, false);
                        $getid3->info['md5_data_source']   = $info_ofr_this_block['md5_string'];

                    } else {

                        $getid3->warning('Expecting block size of 16 in "MD5 " chunk, found '.$block_size.' instead');
                        fseek($getid3->fp, $block_size, SEEK_CUR);

                    }
                    break;


                default:
                    $info_ofr_this_block['offset'] = $block_offset;
                    $info_ofr_this_block['size']   = $block_size;

                    $getid3->warning('Unhandled OptimFROG block type "'.$block_name.'" at offset '.$info_ofr_this_block['offset']);
                    fseek($getid3->fp, $block_size, SEEK_CUR);
                    break;
            }
        }
        
        if (isset($getid3->info['ofr']['TAIL']['offset'])) {
            $getid3->info['avdataend'] = $getid3->info['ofr']['TAIL']['offset'];
        }

        $getid3->info['playtime_seconds'] = (float)$getid3->info['ofr']['OFR ']['total_samples'] / ($getid3->info['audio']['channels'] * $getid3->info['audio']['sample_rate']);
        $getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];

        // move the data chunk after all other chunks (if any)
        // so that the RIFF parser doesn't see EOF when trying
        // to skip over the data chunk
        
        $riff_data = substr($riff_data, 0, 36).substr($riff_data, 44).substr($riff_data, 36, 8);
        
        // Save audio info key
        $saved_info_audio = $getid3->info['audio'];

        // Instantiate riff module and analyze string
        $riff = new getid3_riff($getid3);
        $riff->AnalyzeString($riff_data);
        
        // Restore info key
        $getid3->info['audio'] = $saved_info_audio;
        
        $getid3->info['fileformat'] = 'ofr';

        return true;
    }



    public static function OptimFROGsampleTypeLookup($sample_type) {
        
        static $lookup = array (
            0  => 'unsigned int (8-bit)',
            1  => 'signed int (8-bit)',
            2  => 'unsigned int (16-bit)',
            3  => 'signed int (16-bit)',
            4  => 'unsigned int (24-bit)',
            5  => 'signed int (24-bit)',
            6  => 'unsigned int (32-bit)',
            7  => 'signed int (32-bit)',
            8  => 'float 0.24 (32-bit)',
            9  => 'float 16.8 (32-bit)',
            10 => 'float 24.0 (32-bit)'
        );
        
        return @$lookup[$sample_type];
    }



    public static function OptimFROGbitsPerSampleTypeLookup($sample_type) {

        static $lookup = array (
            0  => 8,
            1  => 8,
            2  => 16,
            3  => 16,
            4  => 24,
            5  => 24,
            6  => 32,
            7  => 32,
            8  => 32,
            9  => 32,
            10 => 32
        );
        
        return @$lookup[$sample_type];
    }



    public static function OptimFROGchannelConfigurationLookup($channel_configuration) {
        
        static $lookup = array (
            0 => 'mono',
            1 => 'stereo'
        );
        
        return @$lookup[$channel_configuration];
    }



    public static function OptimFROGchannelConfigNumChannelsLookup($channel_configuration) {

        static $lookup = array (
            0 => 1,
            1 => 2
        );
        
        return @$lookup[$channel_configuration];
    }



    public static function OptimFROGencoderNameLookup($encoder_id) {

        // version = (encoderID >> 4) + 4500
        // system  =  encoderID & 0xF

        $encoder_version   = number_format(((($encoder_id & 0xF0) >> 4) + 4500) / 1000, 3);
        $encoder_system_id = ($encoder_id & 0x0F);

        static $lookup = array (
            0x00 => 'Windows console',
            0x01 => 'Linux console',
            0x0F => 'unknown'
        );
        return $encoder_version.' ('.(isset($lookup[$encoder_system_id]) ? $lookup[$encoder_system_id] : 'undefined encoder type (0x'.dechex($encoder_system_id).')').')';
    }



    public static function OptimFROGcompressionLookup($compression_id) {

        // mode    = compression >> 3
        // speedup = compression & 0x07

        $compression_mode_id    = ($compression_id & 0xF8) >> 3;
        //$compression_speed_up_id = ($compression_id & 0x07);

        static $lookup = array (
            0x00 => 'fast',
            0x01 => 'normal',
            0x02 => 'high',
            0x03 => 'extra', // extranew (some versions)
            0x04 => 'best',  // bestnew (some versions)
            0x05 => 'ultra',
            0x06 => 'insane',
            0x07 => 'highnew',
            0x08 => 'extranew',
            0x09 => 'bestnew'
        );
        return (isset($lookup[$compression_mode_id]) ? $lookup[$compression_mode_id] : 'undefined mode (0x'.str_pad(dechex($compression_mode_id), 2, '0', STR_PAD_LEFT).')');
    }



    public static function OptimFROGspeedupLookup($compression_id) {

        // mode    = compression >> 3
        // speedup = compression & 0x07

        //$compression_mode_id    = ($compression_id & 0xF8) >> 3;
        $compression_speed_up_id = ($compression_id & 0x07);

        static $lookup = array (
            0x00 => '1x',
            0x01 => '2x',
            0x02 => '4x'
        );

        return (isset($lookup[$compression_speed_up_id]) ? $lookup[$compression_speed_up_id] : 'undefined mode (0x'.dechex($compression_speed_up_id));
    }

}


?>