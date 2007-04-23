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
// | module.audio.monkey.php                                              |
// | Module for analyzing Monkey's Audio files                            |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.monkey.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_monkey extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        // based loosely on code from TMonkey by Jurgen Faul <jfaulØgmx*de>
        // http://jfaul.de/atl  or  http://j-faul.virtualave.net/atl/atl.html
        
        $getid3->info['fileformat']            = 'mac';
        $getid3->info['audio']['dataformat']   = 'mac';
        $getid3->info['audio']['bitrate_mode'] = 'vbr';
        $getid3->info['audio']['lossless']     = true;

        $getid3->info['monkeys_audio']['raw']  = array ();
        $info_monkeys_audio                    = &$getid3->info['monkeys_audio'];
        $info_monkeys_audio_raw                = &$info_monkeys_audio['raw'];

        // Read file header
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $mac_header_data = fread($getid3->fp, 74);

        $info_monkeys_audio_raw['magic'] = 'MAC ';  // Magic bytes

        // Read MAC version
        $info_monkeys_audio_raw['nVersion'] = getid3_lib::LittleEndian2Int(substr($mac_header_data, 4, 2)); // appears to be uint32 in 3.98+
        
        // Parse MAC Header < v3980
        if ($info_monkeys_audio_raw['nVersion'] < 3980) {
        
            getid3_lib::ReadSequence("LittleEndian2Int", $info_monkeys_audio_raw, $mac_header_data, 6,
                array (
                    'nCompressionLevel'     => 2,  
                    'nFormatFlags'          => 2,
                    'nChannels'             => 2,
                    'nSampleRate'           => 4,
                    'nHeaderDataBytes'      => 4,   
                    'nWAVTerminatingBytes'  => 4,
                    'nTotalFrames'          => 4,       
                    'nFinalFrameSamples'    => 4, 
                    'nPeakLevel'            => 4,         
                    'IGNORE-1'              => 2, 
                    'nSeekElements'         => 2
                )
            );
        } 
        
        // Parse MAC Header >= v3980
        else {

            getid3_lib::ReadSequence("LittleEndian2Int", $info_monkeys_audio_raw, $mac_header_data, 8, 
                array (
                    // APE_DESCRIPTOR
                    'nDescriptorBytes'      => 4,
                    'nHeaderBytes'          => 4, 
                    'nSeekTableBytes'       => 4, 
                    'nHeaderDataBytes'      => 4, 
                    'nAPEFrameDataBytes'    => 4, 
                    'nAPEFrameDataBytesHigh'=> 4, 
                    'nTerminatingDataBytes' => 4, 
                    
                    // MD5 - string
                    'cFileMD5'              => -16, 
                    
                    // APE_HEADER
                    'nCompressionLevel'     => 2,
                    'nFormatFlags'          => 2,
                    'nBlocksPerFrame'       => 4,
                    'nFinalFrameBlocks'     => 4,
                    'nTotalFrames'          => 4,
                    'nBitsPerSample'        => 2,
                    'nChannels'             => 2,
                    'nSampleRate'           => 4
                )
            );
        }
        
        // Process data
        $info_monkeys_audio['flags']['8-bit']         = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0001);
        $info_monkeys_audio['flags']['crc-32']        = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0002);
        $info_monkeys_audio['flags']['peak_level']    = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0004);
        $info_monkeys_audio['flags']['24-bit']        = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0008);
        $info_monkeys_audio['flags']['seek_elements'] = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0010);
        $info_monkeys_audio['flags']['no_wav_header'] = (bool)($info_monkeys_audio_raw['nFormatFlags'] & 0x0020);
        
        $info_monkeys_audio['version']                = $info_monkeys_audio_raw['nVersion'] / 1000;
        
        $info_monkeys_audio['compression']            = getid3_monkey::MonkeyCompressionLevelNameLookup($info_monkeys_audio_raw['nCompressionLevel']);
        
        $info_monkeys_audio['bits_per_sample']        = ($info_monkeys_audio['flags']['24-bit'] ? 24 : ($info_monkeys_audio['flags']['8-bit'] ? 8 : 16));
        
        $info_monkeys_audio['channels']               = $info_monkeys_audio_raw['nChannels'];
        
        $getid3->info['audio']['channels']            = $info_monkeys_audio['channels'];
        
        $info_monkeys_audio['sample_rate']            = $info_monkeys_audio_raw['nSampleRate'];
        
        $getid3->info['audio']['sample_rate']         = $info_monkeys_audio['sample_rate'];
        
        if ($info_monkeys_audio['flags']['peak_level']) {
            $info_monkeys_audio['peak_level']         = $info_monkeys_audio_raw['nPeakLevel'];
            $info_monkeys_audio['peak_ratio']         = $info_monkeys_audio['peak_level'] / pow(2, $info_monkeys_audio['bits_per_sample'] - 1);
        }
        
        // MAC >= v3980
        if ($info_monkeys_audio_raw['nVersion'] >= 3980) {
            $info_monkeys_audio['samples']            = (($info_monkeys_audio_raw['nTotalFrames'] - 1) * $info_monkeys_audio_raw['nBlocksPerFrame']) + $info_monkeys_audio_raw['nFinalFrameBlocks'];
        } 
        
        // MAC < v3980
        else {
            $info_monkeys_audio['samples_per_frame']  = getid3_monkey::MonkeySamplesPerFrame($info_monkeys_audio_raw['nVersion'], $info_monkeys_audio_raw['nCompressionLevel']);
            $info_monkeys_audio['samples']            = (($info_monkeys_audio_raw['nTotalFrames'] - 1) * $info_monkeys_audio['samples_per_frame']) + $info_monkeys_audio_raw['nFinalFrameSamples'];
        }
        
        $info_monkeys_audio['playtime']               = $info_monkeys_audio['samples'] / $info_monkeys_audio['sample_rate'];
        
        $getid3->info['playtime_seconds']             = $info_monkeys_audio['playtime'];

        $info_monkeys_audio['compressed_size']        = $getid3->info['avdataend'] - $getid3->info['avdataoffset'];
        $info_monkeys_audio['uncompressed_size']      = $info_monkeys_audio['samples'] * $info_monkeys_audio['channels'] * ($info_monkeys_audio['bits_per_sample'] / 8);
        $info_monkeys_audio['compression_ratio']      = $info_monkeys_audio['compressed_size'] / ($info_monkeys_audio['uncompressed_size'] + $info_monkeys_audio_raw['nHeaderDataBytes']);
        $info_monkeys_audio['bitrate']                = (($info_monkeys_audio['samples'] * $info_monkeys_audio['channels'] * $info_monkeys_audio['bits_per_sample']) / $info_monkeys_audio['playtime']) * $info_monkeys_audio['compression_ratio'];

        $getid3->info['audio']['bitrate']             = $info_monkeys_audio['bitrate'];
        
        $getid3->info['audio']['bits_per_sample']     = $info_monkeys_audio['bits_per_sample'];
        $getid3->info['audio']['encoder']             = 'MAC v'.number_format($info_monkeys_audio['version'], 2);
        $getid3->info['audio']['encoder_options']     = ucfirst($info_monkeys_audio['compression']).' compression';
        
        // MAC >= v3980 - get avdataoffsets from MAC header
        if ($info_monkeys_audio_raw['nVersion'] >= 3980) {
            $getid3->info['avdataoffset'] += $info_monkeys_audio_raw['nDescriptorBytes'] + $info_monkeys_audio_raw['nHeaderBytes'] + $info_monkeys_audio_raw['nSeekTableBytes'] + $info_monkeys_audio_raw['nHeaderDataBytes'];
            $getid3->info['avdataend']    -= $info_monkeys_audio_raw['nTerminatingDataBytes'];
        } 
        
        // MAC < v3980 Add size of MAC header to avdataoffset
        else {
            $getid3->info['avdataoffset'] += 8;
        }

        // Convert md5sum to 32 byte string
        if (@$info_monkeys_audio_raw['cFileMD5']) {
            if ($info_monkeys_audio_raw['cFileMD5'] !== str_repeat("\x00", 16)) {
                $getid3->info['md5_data_source'] = '';
                $md5 = $info_monkeys_audio_raw['cFileMD5'];
                for ($i = 0; $i < strlen($md5); $i++) {
                    $getid3->info['md5_data_source'] .= str_pad(dechex(ord($md5{$i})), 2, '00', STR_PAD_LEFT);
                }
                if (!preg_match('/^[0-9a-f]{32}$/', $getid3->info['md5_data_source'])) {
                    unset($getid3->info['md5_data_source']);
                }
            }
        }
        

        return true;
    }

    
    
    public static function MonkeyCompressionLevelNameLookup($compression_level) {
        
        static $lookup = array (
            0     => 'unknown',
            1000  => 'fast',
            2000  => 'normal',
            3000  => 'high',
            4000  => 'extra-high',
            5000  => 'insane'
        );
        return (isset($lookup[$compression_level]) ? $lookup[$compression_level] : 'invalid');
    }

    
    
    public static function MonkeySamplesPerFrame($version_id, $compression_level) {

        if ($version_id >= 3950) {
            return 73728 * 4;
        } 
        if (($version_id >= 3900)  || (($version_id >= 3800) && ($compression_level == 4000))) {
            return 73728;
        }
        return 9216;
    }

}

?>