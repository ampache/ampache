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
// | module.audio.la.php                                                  |
// | Module for analyzing LA udio files                                   |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.la.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_la extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $raw_data = fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);
    
        $getid3->info['fileformat']          = 'la';
        $getid3->info['audio']['dataformat'] = 'la';
        $getid3->info['audio']['lossless']   = true;

        $getid3->info['la']['version_major'] = (int)$raw_data{2};
        $getid3->info['la']['version_minor'] = (int)$raw_data{3};
        $getid3->info['la']['version']       = (float)$getid3->info['la']['version_major'] + ($getid3->info['la']['version_minor'] / 10);

        $getid3->info['la']['uncompressed_size'] = getid3_lib::LittleEndian2Int(substr($raw_data, 4, 4));
        
        $wave_chunk = substr($raw_data, 8, 4);
        if ($wave_chunk !== 'WAVE') {
            throw new getid3_exception('Expected "WAVE" ('.getid3_lib::PrintHexBytes('WAVE').') at offset 8, found "'.$wave_chunk.'" ('.getid3_lib::PrintHexBytes($wave_chunk).') instead.');
        }
        
        $offset = 12;

        $getid3->info['la']['fmt_size'] = 24;
        if ($getid3->info['la']['version'] >= 0.3) {

            $getid3->info['la']['fmt_size']    = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 4));
            $getid3->info['la']['header_size'] = 49 + $getid3->info['la']['fmt_size'] - 24;
            $offset += 4;

        } else {

            // version 0.2 didn't support additional data blocks
            $getid3->info['la']['header_size'] = 41;
        }

        $fmt_chunk = substr($raw_data, $offset, 4);
        if ($fmt_chunk !== 'fmt ') {
            throw new getid3_exception('Expected "fmt " ('.getid3_lib::PrintHexBytes('fmt ').') at offset '.$offset.', found "'.$fmt_chunk.'" ('.getid3_lib::PrintHexBytes($fmt_chunk).') instead.');
        }
        $offset += 4;
        
        $fmt_size = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 4));
        $offset += 4;

        $getid3->info['la']['raw']['format'] = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 2));
        $offset += 2;
        
        getid3_lib::ReadSequence('LittleEndian2Int', $getid3->info['la'], $raw_data, $offset,
            array (
                'channels'         => 2,
                'sample_rate'      => 4,
                'bytes_per_second' => 4,
                'bytes_per_sample' => 2,
                'bits_per_sample'  => 2,
                'samples'          => 4
            )
        );
        $offset += 18;
        
        $getid3->info['la']['raw']['flags'] = getid3_lib::LittleEndian2Int($raw_data{$offset++});
        
        $getid3->info['la']['flags']['seekable']             = (bool)($getid3->info['la']['raw']['flags'] & 0x01);
        if ($getid3->info['la']['version'] >= 0.4) {
            $getid3->info['la']['flags']['high_compression'] = (bool)($getid3->info['la']['raw']['flags'] & 0x02);
        }

        $getid3->info['la']['original_crc'] = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 4));
        $offset += 4;

        // mikeØbevin*de
        // Basically, the blocksize/seekevery are 61440/19 in La0.4 and 73728/16
        // in earlier versions. A seekpoint is added every blocksize * seekevery
        // samples, so 4 * int(totalSamples / (blockSize * seekEvery)) should
        // give the number of bytes used for the seekpoints. Of course, if seeking
        // is disabled, there are no seekpoints stored.
        
        if ($getid3->info['la']['version'] >= 0.4) {
            $getid3->info['la']['blocksize'] = 61440;
            $getid3->info['la']['seekevery'] = 19;
        } else {
            $getid3->info['la']['blocksize'] = 73728;
            $getid3->info['la']['seekevery'] = 16;
        }

        $getid3->info['la']['seekpoint_count'] = 0;
        if ($getid3->info['la']['flags']['seekable']) {
            $getid3->info['la']['seekpoint_count'] = floor($getid3->info['la']['samples'] / ($getid3->info['la']['blocksize'] * $getid3->info['la']['seekevery']));

            for ($i = 0; $i < $getid3->info['la']['seekpoint_count']; $i++) {
                $getid3->info['la']['seekpoints'][] = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 4));
                $offset += 4;
            }
        }

        if ($getid3->info['la']['version'] >= 0.3) {

            // Following the main header information, the program outputs all of the
            // seekpoints. Following these is what I called the 'footer start',
            // i.e. the position immediately after the La audio data is finished.
        
            $getid3->info['la']['footerstart'] = getid3_lib::LittleEndian2Int(substr($raw_data, $offset, 4));
            $offset += 4;

            if ($getid3->info['la']['footerstart'] > $getid3->info['filesize']) {
                $getid3->warning('FooterStart value points to offset '.$getid3->info['la']['footerstart'].' which is beyond end-of-file ('.$getid3->info['filesize'].')');
                $getid3->info['la']['footerstart'] = $getid3->info['filesize'];
            }

        } else {

            // La v0.2 didn't have FooterStart value
            $getid3->info['la']['footerstart'] = $getid3->info['avdataend'];

        }

        if ($getid3->info['la']['footerstart'] < $getid3->info['avdataend']) {
        
            // Create riff header
            $riff_data = 'WAVE';
            if ($getid3->info['la']['version'] == 0.2) {
                $riff_data .= substr($raw_data, 12, 24);
            } else {
                $riff_data .= substr($raw_data, 16, 24);
            }
            if ($getid3->info['la']['footerstart'] < $getid3->info['avdataend']) {
                fseek($getid3->fp, $getid3->info['la']['footerstart'], SEEK_SET);
                $riff_data .= fread($getid3->fp, $getid3->info['avdataend'] - $getid3->info['la']['footerstart']);
            }
            $riff_data = 'RIFF'.getid3_lib::LittleEndian2String(strlen($riff_data), 4, false).$riff_data;
            
            // Clone getid3 - messing with offsets - better safe than sorry
            $clone = clone $getid3;
            
            // Analyze clone by string
            $riff = new getid3_riff($clone);
            $riff->AnalyzeString($riff_data);
            
            // Import from clone and destroy
            $getid3->info['riff']   = $clone->info['riff'];
            $getid3->warnings($clone->warnings());
            unset($clone);
        }

        // $getid3->info['avdataoffset'] should be zero to begin with, but just in case it's not, include the addition anyway
        $getid3->info['avdataend']    = $getid3->info['avdataoffset'] + $getid3->info['la']['footerstart'];
        $getid3->info['avdataoffset'] = $getid3->info['avdataoffset'] + $offset;

        $getid3->info['la']['compression_ratio']  = (float)(($getid3->info['avdataend'] - $getid3->info['avdataoffset']) / $getid3->info['la']['uncompressed_size']);
        $getid3->info['playtime_seconds']         = (float)($getid3->info['la']['samples'] / $getid3->info['la']['sample_rate']) / $getid3->info['la']['channels'];

        $getid3->info['audio']['bitrate']         = ($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8 / $getid3->info['playtime_seconds'];
        $getid3->info['audio']['bits_per_sample'] = $getid3->info['la']['bits_per_sample'];

        $getid3->info['audio']['channels']        = $getid3->info['la']['channels'];
        $getid3->info['audio']['sample_rate']     = (int)$getid3->info['la']['sample_rate'];
        $getid3->info['audio']['encoder']         = 'LA v'.$getid3->info['la']['version'];

        return true;
    }

}


?>