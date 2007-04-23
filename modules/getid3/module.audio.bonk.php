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
// | module.audio.bonk.php                                                |
// | Module for analyzing BONK audio files                                |
// | dependencies: module.tag.id3v2.php (optional)                        |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.bonk.php,v 1.3 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_bonk extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        $getid3->info['bonk'] = array ();
        $info_bonk = &$getid3->info['bonk'];

        $info_bonk['dataoffset'] = $getid3->info['avdataoffset'];
        $info_bonk['dataend']    = $getid3->info['avdataend'];

        
        // Scan-from-end method, for v0.6 and higher
        fseek($getid3->fp, $info_bonk['dataend'] - 8, SEEK_SET);
        $possible_bonk_tag = fread($getid3->fp, 8);
        while (getid3_bonk::BonkIsValidTagName(substr($possible_bonk_tag, 4, 4), true)) {
            $bonk_tag_size = getid3_lib::LittleEndian2Int(substr($possible_bonk_tag, 0, 4));
            fseek($getid3->fp, 0 - $bonk_tag_size, SEEK_CUR);
            $bonk_tag_offset = ftell($getid3->fp);
            $tag_header_test = fread($getid3->fp, 5);
            if (($tag_header_test{0} != "\x00") || (substr($possible_bonk_tag, 4, 4) != strtolower(substr($possible_bonk_tag, 4, 4)))) {
                throw new getid3_exception('Expecting "Ø'.strtoupper(substr($possible_bonk_tag, 4, 4)).'" at offset '.$bonk_tag_offset.', found "'.$tag_header_test.'"');
            }
            $bonk_tag_name = substr($tag_header_test, 1, 4);

            $info_bonk[$bonk_tag_name]['size']   = $bonk_tag_size;
            $info_bonk[$bonk_tag_name]['offset'] = $bonk_tag_offset;
            $this->HandleBonkTags($bonk_tag_name);
            
            $next_tag_end_offset = $bonk_tag_offset - 8;
            if ($next_tag_end_offset < $info_bonk['dataoffset']) {
                if (empty($getid3->info['audio']['encoder'])) {
                    $getid3->info['audio']['encoder'] = 'Extended BONK v0.9+';
                }
                return true;
            }
            fseek($getid3->fp, $next_tag_end_offset, SEEK_SET);
            $possible_bonk_tag = fread($getid3->fp, 8);
        }

        // Seek-from-beginning method for v0.4 and v0.5
        if (empty($info_bonk['BONK'])) {
            fseek($getid3->fp, $info_bonk['dataoffset'], SEEK_SET);
            do {
                $tag_header_test = fread($getid3->fp, 5);
                switch ($tag_header_test) {
                    case "\x00".'BONK':
                        if (empty($getid3->info['audio']['encoder'])) {
                            $getid3->info['audio']['encoder'] = 'BONK v0.4';
                        }
                        break;

                    case "\x00".'INFO':
                        $getid3->info['audio']['encoder'] = 'Extended BONK v0.5';
                        break;

                    default:
                        break 2;
                }
                $bonk_tag_name = substr($tag_header_test, 1, 4);
                $info_bonk[$bonk_tag_name]['size']   = $info_bonk['dataend'] - $info_bonk['dataoffset'];
                $info_bonk[$bonk_tag_name]['offset'] = $info_bonk['dataoffset'];
                $this->HandleBonkTags($bonk_tag_name);

            } while (true);
        }


        // Parse META block for v0.6 - v0.8
        if (!@$info_bonk['INFO'] && isset($info_bonk['META']['tags']['info'])) {
            fseek($getid3->fp, $info_bonk['META']['tags']['info'], SEEK_SET);
            $tag_header_test = fread($getid3->fp, 5);
            if ($tag_header_test == "\x00".'INFO') {
                $getid3->info['audio']['encoder'] = 'Extended BONK v0.6 - v0.8';

                $bonk_tag_name = substr($tag_header_test, 1, 4);
                $info_bonk[$bonk_tag_name]['size']   = $info_bonk['dataend'] - $info_bonk['dataoffset'];
                $info_bonk[$bonk_tag_name]['offset'] = $info_bonk['dataoffset'];
                $this->HandleBonkTags($bonk_tag_name);
            }
        }

        if (empty($getid3->info['audio']['encoder'])) {
            $getid3->info['audio']['encoder'] = 'Extended BONK v0.9+';
        }
        if (empty($info_bonk['BONK'])) {
            unset($getid3->info['bonk']);
        }
        return true;

    }


    
    private function HandleBonkTags(&$bonk_tag_name) {

        // Shortcut to getid3 pointer
        $getid3 = $this->getid3;
        $info_audio = &$getid3->info['audio'];

        switch ($bonk_tag_name) {
            
            case 'BONK':
                // shortcut
                $info_bonk_BONK = &$getid3->info['bonk']['BONK'];

                $bonk_data = "\x00".'BONK'.fread($getid3->fp, 17);
                
                getid3_lib::ReadSequence('LittleEndian2Int', $info_bonk_BONK, $bonk_data, 5, 
                    array (
                        'version'            => 1,
                        'number_samples'     => 4,
                        'sample_rate'        => 4,
                        'channels'           => 1,
                        'lossless'           => 1,
                        'joint_stereo'       => 1,
                        'number_taps'        => 2,
                        'downsampling_ratio' => 1,
                        'samples_per_packet' => 2
                    )
                );
                
                $info_bonk_BONK['lossless']     = (bool)$info_bonk_BONK['lossless'];
                $info_bonk_BONK['joint_stereo'] = (bool)$info_bonk_BONK['joint_stereo'];

                $getid3->info['avdataoffset']   = $info_bonk_BONK['offset'] + 5 + 17;
                $getid3->info['avdataend']      = $info_bonk_BONK['offset'] + $info_bonk_BONK['size'];

                $getid3->info['fileformat']     = 'bonk';
                $info_audio['dataformat']       = 'bonk';
                $info_audio['bitrate_mode']     = 'vbr'; // assumed
                $info_audio['channels']         = $info_bonk_BONK['channels'];
                $info_audio['sample_rate']      = $info_bonk_BONK['sample_rate'];
                $info_audio['channelmode']      = $info_bonk_BONK['joint_stereo'] ? 'joint stereo' : 'stereo';
                $info_audio['lossless']         = $info_bonk_BONK['lossless'];
                $info_audio['codec']            = 'bonk';

                $getid3->info['playtime_seconds'] = $info_bonk_BONK['number_samples'] / ($info_bonk_BONK['sample_rate'] * $info_bonk_BONK['channels']);
                if ($getid3->info['playtime_seconds'] > 0) {
                    $info_audio['bitrate'] = (($getid3->info['bonk']['dataend'] - $getid3->info['bonk']['dataoffset']) * 8) / $getid3->info['playtime_seconds'];
                }
                break;

            case 'INFO':
                // shortcut
                $info_bonk_INFO = &$getid3->info['bonk']['INFO'];

                $info_bonk_INFO['version'] = getid3_lib::LittleEndian2Int(fread($getid3->fp, 1));
                $info_bonk_INFO['entries_count'] = 0;
                $next_info_data_pair = fread($getid3->fp, 5);
                if (!getid3_bonk::BonkIsValidTagName(substr($next_info_data_pair, 1, 4))) {
                    while (!feof($getid3->fp)) {
                        $next_info_data_pair = fread($getid3->fp, 5);
                        if (getid3_bonk::BonkIsValidTagName(substr($next_info_data_pair, 1, 4))) {
                            fseek($getid3->fp, -5, SEEK_CUR);
                            break;
                        }
                        $info_bonk_INFO['entries_count']++;
                    }
                }
                break;

            case 'META':
                $bonk_data = "\x00".'META'.fread($getid3->fp, $getid3->info['bonk']['META']['size'] - 5);
                $getid3->info['bonk']['META']['version'] = getid3_lib::LittleEndian2Int(substr($bonk_data,  5, 1));

                $meta_tag_entries = floor(((strlen($bonk_data) - 8) - 6) / 8); // BonkData - xxxxmeta - ØMETA
                $offset = 6;
                for ($i = 0; $i < $meta_tag_entries; $i++) {
                    $meta_entry_tag_name   = substr($bonk_data, $offset, 4);
                    $offset += 4;
                    $meta_entry_tag_offset = getid3_lib::LittleEndian2Int(substr($bonk_data, $offset, 4));
                    $offset += 4;
                    $getid3->info['bonk']['META']['tags'][$meta_entry_tag_name] = $meta_entry_tag_offset;
                }
                break;

            case ' ID3':
                $info_audio['encoder'] = 'Extended BONK v0.9+';

                // ID3v2 checking is optional
                if (class_exists('getid3_id3v2')) {
                    
                    $id3v2 = new getid3_id3v2($getid3);
                    $id3v2->option_starting_offset = $getid3->info['bonk'][' ID3']['offset'] + 2;
                    $getid3->info['bonk'][' ID3']['valid'] = $id3v2->Analyze();
                }
                break;

            default:
                $getid3->warning('Unexpected Bonk tag "'.$bonk_tag_name.'" at offset '.$getid3->info['bonk'][$bonk_tag_name]['offset']);
                break;

        }
    }


    
    public static function BonkIsValidTagName($possible_bonk_tag, $ignore_case=false) {
                                                                              
        $ignore_case = $ignore_case ? 'i' : '';                                                                              
        return preg_match('/^(BONK|INFO| ID3|META)$/'.$ignore_case, $possible_bonk_tag);
    }

}


?>