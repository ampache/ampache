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
// | module.audio.voc.php                                                 |
// | Module for analyzing Creative VOC Audio files.                       |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.voc.php,v 1.3 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_voc extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        $original_av_data_offset = $getid3->info['avdataoffset'];
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $voc_header= fread($getid3->fp, 26);

        // Magic bytes: 'Creative Voice File'

        $info_audio = &$getid3->info['audio'];
        $getid3->info['voc'] = array ();
        $info_voc = &$getid3->info['voc'];

        $getid3->info['fileformat']    = 'voc';
        $info_audio['dataformat']      = 'voc';
        $info_audio['bitrate_mode']    = 'cbr';
        $info_audio['lossless']        = true;
        $info_audio['channels']        = 1;         // might be overriden below
        $info_audio['bits_per_sample'] = 8;         // might be overriden below

        // byte #     Description
        // ------     ------------------------------------------
        // 00-12      'Creative Voice File'
        // 13         1A (eof to abort printing of file)
        // 14-15      Offset of first datablock in .voc file (std 1A 00 in Intel Notation)
        // 16-17      Version number (minor,major) (VOC-HDR puts 0A 01)
        // 18-19      2's Comp of Ver. # + 1234h (VOC-HDR puts 29 11)

        getid3_lib::ReadSequence('LittleEndian2Int', $info_voc['header'], $voc_header, 20,
            array (
                'datablock_offset' => 2, 
                'minor_version'    => 1, 
                'major_version'    => 1  
            )
        );

        do {
            $block_offset = ftell($getid3->fp);
            $block_data   = fread($getid3->fp, 4);
            $block_type   = ord($block_data{0});
            $block_size   = getid3_lib::LittleEndian2Int(substr($block_data, 1, 3));
            $this_block   = array ();

            @$info_voc['blocktypes'][$block_type]++;
            
            switch ($block_type) {
                
                case 0:  // Terminator
                    // do nothing, we'll break out of the loop down below
                    break;

                case 1:  // Sound data
                    $block_data .= fread($getid3->fp, 2);
                    if ($getid3->info['avdataoffset'] <= $original_av_data_offset) {
                        $getid3->info['avdataoffset'] = ftell($getid3->fp);
                    }
                    fseek($getid3->fp, $block_size - 2, SEEK_CUR);

                    getid3_lib::ReadSequence('LittleEndian2Int', $this_block, $block_data, 4,
                        array (
                            'sample_rate_id'   => 1,
                            'compression_type' => 1
                        )
                    );

                    $this_block['compression_name'] = getid3_voc::VOCcompressionTypeLookup($this_block['compression_type']);
                    if ($this_block['compression_type'] <= 3) {
                        $info_voc['compressed_bits_per_sample'] = (int)(str_replace('-bit', '', $this_block['compression_name']));
                    }

                    // Less accurate sample_rate calculation than the Extended block (#8) data (but better than nothing if Extended Block is not available)
                    if (empty($info_audio['sample_rate'])) {
                        // SR byte = 256 - (1000000 / sample_rate)
                        $info_audio['sample_rate'] = (int)floor((1000000 / (256 - $this_block['sample_rate_id'])) / $info_audio['channels']);
                    }
                    break;

                case 2:  // Sound continue
                case 3:  // Silence
                case 4:  // Marker
                case 6:  // Repeat
                case 7:  // End repeat
                    // nothing useful, just skip
                    fseek($getid3->fp, $block_size, SEEK_CUR);
                    break;

                case 8:  // Extended
                    $block_data .= fread($getid3->fp, 4);

                    //00-01  Time Constant:
                    //   Mono: 65536 - (256000000 / sample_rate)
                    // Stereo: 65536 - (256000000 / (sample_rate * 2))
                    getid3_lib::ReadSequence('LittleEndian2Int', $this_block, $block_data, 4,
                        array (
                            'time_constant' => 2, 
                            'pack_method'   => 1, 
                            'stereo'        => 1        
                        )
                    );
                    $this_block['stereo']      = (bool)$this_block['stereo'];
                    
                    $info_audio['channels']    = ($this_block['stereo'] ? 2 : 1);
                    $info_audio['sample_rate'] = (int)floor((256000000 / (65536 - $this_block['time_constant'])) / $info_audio['channels']);
                    break;

                case 9:  // data block that supersedes blocks 1 and 8. Used for stereo, 16 bit
                    $block_data .= fread($getid3->fp, 12);
                    if ($getid3->info['avdataoffset'] <= $original_av_data_offset) {
                        $getid3->info['avdataoffset'] = ftell($getid3->fp);
                    }
                    fseek($getid3->fp, $block_size - 12, SEEK_CUR);

                    getid3_lib::ReadSequence('LittleEndian2Int', $this_block, $block_data, 4,
                        array (
                            'sample_rate'     => 4,
                            'bits_per_sample' => 1,
                            'channels'        => 1, 
                            'wFormat'         => 2 
                        )
                    );
                    
                    $this_block['compression_name'] = getid3_voc::VOCwFormatLookup($this_block['wFormat']);
                    if (getid3_voc::VOCwFormatActualBitsPerSampleLookup($this_block['wFormat'])) {
                        $info_voc['compressed_bits_per_sample'] = getid3_voc::VOCwFormatActualBitsPerSampleLookup($this_block['wFormat']);
                    }

                    $info_audio['sample_rate']     = $this_block['sample_rate'];
                    $info_audio['bits_per_sample'] = $this_block['bits_per_sample'];
                    $info_audio['channels']        = $this_block['channels'];
                    break;

                default:
                    $getid3->warning('Unhandled block type "'.$block_type.'" at offset '.$block_offset);
                    fseek($getid3->fp, $block_size, SEEK_CUR);
                    break;
            }

            if (!empty($this_block)) {
                $this_block['block_offset']  = $block_offset;
                $this_block['block_size']    = $block_size;
                $this_block['block_type_id'] = $block_type;
                $info_voc['blocks'][] = $this_block;
            }

        } while (!feof($getid3->fp) && ($block_type != 0));

        // Terminator block doesn't have size field, so seek back 3 spaces
        fseek($getid3->fp, -3, SEEK_CUR);

        ksort($info_voc['blocktypes']);

        if (!empty($info_voc['compressed_bits_per_sample'])) {
            $getid3->info['playtime_seconds'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / ($info_voc['compressed_bits_per_sample'] * $info_audio['channels'] * $info_audio['sample_rate']);
            $info_audio['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
        }

        return true;
    }



    public static function VOCcompressionTypeLookup($index) {
        
        static $lookup = array (
            0 => '8-bit',
            1 => '4-bit',
            2 => '2.6-bit',
            3 => '2-bit'
        );
        return (isset($lookup[$index]) ? $lookup[$index] : 'Multi DAC ('.($index - 3).') channels');
    }



    public static function VOCwFormatLookup($index) {
        
        static $lookup = array (
            0x0000 => '8-bit unsigned PCM',
            0x0001 => 'Creative 8-bit to 4-bit ADPCM',
            0x0002 => 'Creative 8-bit to 3-bit ADPCM',
            0x0003 => 'Creative 8-bit to 2-bit ADPCM',
            0x0004 => '16-bit signed PCM',
            0x0006 => 'CCITT a-Law',
            0x0007 => 'CCITT u-Law',
            0x2000 => 'Creative 16-bit to 4-bit ADPCM'
        );
        return (isset($lookup[$index]) ? $lookup[$index] : false);
    }



    public static function VOCwFormatActualBitsPerSampleLookup($index) {
        
        static $lookup = array (
            0x0000 => 8,
            0x0001 => 4,
            0x0002 => 3,
            0x0003 => 2,
            0x0004 => 16,
            0x0006 => 8,
            0x0007 => 8,
            0x2000 => 4
        );
        return (isset($lookup[$index]) ? $lookup[$index] : false);
    }

}


?>