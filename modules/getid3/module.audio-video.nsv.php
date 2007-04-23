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
// | module.audio-video.nsv.php                                           |
// | module for analyzing Nullsoft NSV files                              |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.nsv.php,v 1.3 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_nsv extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        $getid3->info['fileformat']          = 'nsv';
        $getid3->info['audio']['dataformat'] = 'nsv';
        $getid3->info['video']['dataformat'] = 'nsv';
        $getid3->info['audio']['lossless']   = false;
        $getid3->info['video']['lossless']   = false;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $nsv_header = fread($getid3->fp, 4);

        switch ($nsv_header) {
            
            case 'NSVs':
                $this->getNSVsHeader();
                break;

            case 'NSVf':
                if ($this->getNSVfHeader()) {
                        $this->getNSVsHeader($getid3->info['nsv']['NSVf']['header_length']);
                }
                break;

            default:
                throw new getid3_exception('Expecting "NSVs" or "NSVf" at offset '.$getid3->info['avdataoffset'].', found "'.$nsv_header.'"');
                break;
        }

        if (!isset($getid3->info['nsv']['NSVf'])) {
            $getid3->warning('NSVf header not present - cannot calculate playtime or bitrate');
        }

        return true;
    }



    private function getNSVsHeader($file_offset = 0) {
        
        $getid3 = $this->getid3;
        
        fseek($getid3->fp, $file_offset, SEEK_SET);
        $nsvs_header = fread($getid3->fp, 28);
        
        $getid3->info['nsv']['NSVs'] = array ();
        $info_nsv_NSVs = &$getid3->info['nsv']['NSVs'];

        $info_nsv_NSVs['identifier'] = substr($nsvs_header, 0, 4);
        if ($info_nsv_NSVs['identifier'] != 'NSVs') {
            throw new getid3_exception('expected "NSVs" at offset ('.$file_offset.'), found "'.$info_nsv_NSVs['identifier'].'" instead');
        }
        
        $info_nsv_NSVs['offset'] = $file_offset;
        
        getid3_lib::ReadSequence('LittleEndian2Int', $info_nsv_NSVs, $nsvs_header, 4,
            array (
                'video_codec'     => -4,    // string
                'audio_codec'     => -4,    // string
                'resolution_x'    => 2,
                'resolution_y'    => 2,
                'framerate_index' => 1,
            )
        );

        if ($info_nsv_NSVs['audio_codec'] == 'PCM ') {
        
            getid3_lib::ReadSequence('LittleEndian2Int', $info_nsv_NSVs, $nsvs_header, 24,
                array (
                    'bits_channel' => 1,
                    'channels'     => 1,
                    'sample_rate'  => 2
                )
            );
            $getid3->info['audio']['sample_rate'] = $info_nsv_NSVs['sample_rate'];
            
        }

        $getid3->info['video']['resolution_x']       = $info_nsv_NSVs['resolution_x'];
        $getid3->info['video']['resolution_y']       = $info_nsv_NSVs['resolution_y'];
        $info_nsv_NSVs['frame_rate']                 = getid3_nsv::NSVframerateLookup($info_nsv_NSVs['framerate_index']);
        $getid3->info['video']['frame_rate']         = $info_nsv_NSVs['frame_rate'];
        $getid3->info['video']['bits_per_sample']    = 24;
        $getid3->info['video']['pixel_aspect_ratio'] = (float)1;

        return true;
    }



    private function getNSVfHeader($file_offset = 0, $get_toc_offsets=false) {
        
        $getid3 = $this->getid3;
        
        fseek($getid3->fp, $file_offset, SEEK_SET);
        $nsvf_header = fread($getid3->fp, 28);
        
        $getid3->info['nsv']['NSVf'] = array ();
        $info_nsv_NSVf = &$getid3->info['nsv']['NSVf'];
        
        $info_nsv_NSVf['identifier'] = substr($nsvf_header, 0, 4);
        if ($info_nsv_NSVf['identifier'] != 'NSVf') {
            throw new getid3_exception('expected "NSVf" at offset ('.$file_offset.'), found "'.$info_nsv_NSVf['identifier'].'" instead');
        }

        $getid3->info['nsv']['NSVs']['offset']        = $file_offset;

        getid3_lib::ReadSequence('LittleEndian2Int', $info_nsv_NSVf, $nsvf_header, 4,
            array (
                'header_length' => 4,
                'file_size'     => 4,
                'playtime_ms'   => 4,
                'meta_size'     => 4,
                'TOC_entries_1' => 4,
                'TOC_entries_2' => 4
            )
        );
                
        if ($info_nsv_NSVf['playtime_ms'] == 0) {
            throw new getid3_exception('Corrupt NSV file: NSVf.playtime_ms == zero');
        }
        
        if ($info_nsv_NSVf['file_size'] > $getid3->info['avdataend']) {
            $getid3->warning('truncated file - NSVf header indicates '.$info_nsv_NSVf['file_size'].' bytes, file actually '.$getid3->info['avdataend'].' bytes');
        }

        $nsvf_header .= fread($getid3->fp, $info_nsv_NSVf['meta_size'] + (4 * $info_nsv_NSVf['TOC_entries_1']) + (4 * $info_nsv_NSVf['TOC_entries_2']));
        $nsvf_headerlength = strlen($nsvf_header);
        $info_nsv_NSVf['metadata'] = substr($nsvf_header, 28, $info_nsv_NSVf['meta_size']);

        $offset = 28 + $info_nsv_NSVf['meta_size'];
        if ($get_toc_offsets) {
            $toc_counter = 0;
            while ($toc_counter < $info_nsv_NSVf['TOC_entries_1']) {
                if ($toc_counter < $info_nsv_NSVf['TOC_entries_1']) {
                    $info_nsv_NSVf['TOC_1'][$toc_counter] = getid3_lib::LittleEndian2Int(substr($nsvf_header, $offset, 4));
                    $offset += 4;
                    $toc_counter++;
                }
            }
        }

        if (trim($info_nsv_NSVf['metadata']) != '') {
            $info_nsv_NSVf['metadata'] = str_replace('`', "\x01", $info_nsv_NSVf['metadata']);
            $comment_pair_array = explode("\x01".' ', $info_nsv_NSVf['metadata']);
            foreach ($comment_pair_array as $comment_pair) {
                if (strstr($comment_pair, '='."\x01")) {
                    list($key, $value) = explode('='."\x01", $comment_pair, 2);
                    $getid3->info['nsv']['comments'][strtolower($key)][] = trim(str_replace("\x01", '', $value));
                }
            }
        }

        $getid3->info['playtime_seconds'] = $info_nsv_NSVf['playtime_ms'] / 1000;
        $getid3->info['bitrate']          = ($info_nsv_NSVf['file_size'] * 8) / $getid3->info['playtime_seconds'];

        return true;
    }



    public static function NSVframerateLookup($frame_rate_index) {
        
        if ($frame_rate_index <= 127) {
            return (float)$frame_rate_index;
        }

        static $lookup = array (
            129 => 29.970,
            131 => 23.976,
            133 => 14.985,
            197 => 59.940,
            199 => 47.952
        );
        return (isset($lookup[$frame_rate_index]) ? $lookup[$frame_rate_index] : false);
    }

}


?>