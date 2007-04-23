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
// | module.tag.lyrics3.php                                               |
// | module for analyzing Lyrics3 tags                                    |
// | dependencies: module.tag.apetag.php (optional)                       |
// +----------------------------------------------------------------------+
//
// $Id: module.tag.lyrics3.php,v 1.5 2006/11/16 22:04:23 ah Exp $


class getid3_lyrics3 extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        fseek($getid3->fp, (0 - 128 - 9 - 6), SEEK_END);  // end - ID3v1 - LYRICSEND - [Lyrics3size]
        $lyrics3_id3v1 = fread($getid3->fp, 128 + 9 + 6);
        $lyrics3_lsz   = substr($lyrics3_id3v1,  0,   6); // Lyrics3size
        $lyrics3_end   = substr($lyrics3_id3v1,  6,   9); // LYRICSEND or LYRICS200
        $id3v1_tag     = substr($lyrics3_id3v1, 15, 128); // ID3v1

        // Lyrics3v1, ID3v1, no APE
        if ($lyrics3_end == 'LYRICSEND') {

            $lyrics3_size    = 5100;
            $lyrics3_offset  = filesize($getid3->filename) - 128 - $lyrics3_size;
            $lyrics3_version = 1;
        } 

        // Lyrics3v2, ID3v1, no APE
        elseif ($lyrics3_end == 'LYRICS200') {

            // LSZ = lyrics + 'LYRICSBEGIN'; add 6-byte size field; add 'LYRICS200'
            $lyrics3_size    = $lyrics3_lsz + 6 + strlen('LYRICS200');
            $lyrics3_offset  = filesize($getid3->filename) - 128 - $lyrics3_size;
            $lyrics3_version = 2;
        } 
        
        // Lyrics3v1, no ID3v1, no APE
        elseif (substr(strrev($lyrics3_id3v1), 0, 9) == 'DNESCIRYL') {            // strrev('LYRICSEND') = 'DNESCIRYL'

            $lyrics3_size    = 5100;
            $lyrics3_offset  = filesize($getid3->filename) - $lyrics3_size;
            $lyrics3_version = 1;
            $lyrics3_offset  = filesize($getid3->filename) - $lyrics3_size;
        } 
    
        // Lyrics3v2, no ID3v1, no APE
        elseif (substr(strrev($lyrics3_id3v1), 0, 9) == '002SCIRYL') {             // strrev('LYRICS200') = '002SCIRYL'

            $lyrics3_size    = strrev(substr(strrev($lyrics3_id3v1), 9, 6)) + 15;   // LSZ = lyrics + 'LYRICSBEGIN'; add 6-byte size field; add 'LYRICS200'  // 15 = 6 + strlen('LYRICS200')
            $lyrics3_offset  = filesize($getid3->filename) - $lyrics3_size;
            $lyrics3_version = 2;
        } 
    
        elseif (isset($getid3->info['ape']['tag_offset_start']) && ($getid3->info['ape']['tag_offset_start'] > 15)) {

            fseek($getid3->fp, $getid3->info['ape']['tag_offset_start'] - 15, SEEK_SET);
            $lyrics3_lsz = fread($getid3->fp, 6);
            $lyrics3_end = fread($getid3->fp, 9);

            
            // Lyrics3v1, APE, maybe ID3v1
            if ($lyrics3_end == 'LYRICSEND') {

                $lyrics3_size    = 5100;
                $lyrics3_offset  = $getid3->info['ape']['tag_offset_start'] - $lyrics3_size;
                $getid3->info['avdataend'] = $lyrics3_offset;
                $lyrics3_version = 1;
                $getid3->warning('APE tag located after Lyrics3, will probably break Lyrics3 compatability');
            } 
        
            
            // Lyrics3v2, APE, maybe ID3v1
            elseif ($lyrics3_end == 'LYRICS200') {

                $lyrics3_size    = $lyrics3_lsz + 15; // LSZ = lyrics + 'LYRICSBEGIN'; add 6-byte size field; add 'LYRICS200'
                $lyrics3_offset  = $getid3->info['ape']['tag_offset_start'] - $lyrics3_size;
                $lyrics3_version = 2;
                $getid3->warning('APE tag located after Lyrics3, will probably break Lyrics3 compatability');

            }
        }
        
        
        //// GetLyrics3Data()
        
        
        if (isset($lyrics3_offset)) {
            
            $getid3->info['avdataend'] = $lyrics3_offset;
            
            if ($lyrics3_size <= 0) {
                return false;
            }

            fseek($getid3->fp, $lyrics3_offset, SEEK_SET);
            $raw_data = fread($getid3->fp, $lyrics3_size);
    
            if (substr($raw_data, 0, 11) != 'LYRICSBEGIN') {
                if (strpos($raw_data, 'LYRICSBEGIN') !== false) {
    
                    $getid3->warning('"LYRICSBEGIN" expected at '.$lyrics3_offset.' but actually found at '.($lyrics3_offset + strpos($raw_data, 'LYRICSBEGIN')).' - this is invalid for Lyrics3 v'.$lyrics3_version);
                    $getid3->info['avdataend'] = $lyrics3_offset + strpos($raw_data, 'LYRICSBEGIN');
                    $parsed_lyrics3['tag_offset_start'] = $getid3->info['avdataend'];
                    $raw_data = substr($raw_data, strpos($raw_data, 'LYRICSBEGIN'));
                    $lyrics3_size = strlen($raw_data);
                }
                else {
                    throw new getid3_exception('"LYRICSBEGIN" expected at '.$lyrics3_offset.' but found "'.substr($raw_data, 0, 11).'" instead.');
                }
    
            }
    
            $parsed_lyrics3['raw']['lyrics3version'] = $lyrics3_version;
            $parsed_lyrics3['raw']['lyrics3tagsize'] = $lyrics3_size;
            $parsed_lyrics3['tag_offset_start']      = $lyrics3_offset;
            $parsed_lyrics3['tag_offset_end']        = $lyrics3_offset + $lyrics3_size;
    
            switch ($lyrics3_version) {
    
                case 1:
                    if (substr($raw_data, strlen($raw_data) - 9, 9) == 'LYRICSEND') {
                        $parsed_lyrics3['raw']['LYR'] = trim(substr($raw_data, 11, strlen($raw_data) - 11 - 9));
                        getid3_lyrics3::Lyrics3LyricsTimestampParse($parsed_lyrics3);
                    }
                    else {
                        throw new getid3_exception('"LYRICSEND" expected at '.(ftell($getid3->fp) - 11 + $lyrics3_size - 9).' but found "'.substr($raw_data, strlen($raw_data) - 9, 9).'" instead.');
                    }
                    break;
    
                case 2:
                    if (substr($raw_data, strlen($raw_data) - 9, 9) == 'LYRICS200') {
                        $parsed_lyrics3['raw']['unparsed'] = substr($raw_data, 11, strlen($raw_data) - 11 - 9 - 6); // LYRICSBEGIN + LYRICS200 + LSZ
                        $raw_data = $parsed_lyrics3['raw']['unparsed'];
                        while (strlen($raw_data) > 0) {
                            $fieldname = substr($raw_data, 0, 3);
                            $fieldsize = (int)substr($raw_data, 3, 5);
                            $parsed_lyrics3['raw'][$fieldname] = substr($raw_data, 8, $fieldsize);
                            $raw_data  = substr($raw_data, 3 + 5 + $fieldsize);
                        }
    
                        if (isset($parsed_lyrics3['raw']['IND'])) {
                            $i = 0;
                            foreach (array ('lyrics', 'timestamps', 'inhibitrandom') as $flagname) {
                                if (strlen($parsed_lyrics3['raw']['IND']) > ++$i) {
                                    $parsed_lyrics3['flags'][$flagname] = getid3_lyrics3::IntString2Bool(substr($parsed_lyrics3['raw']['IND'], $i, 1));
                                }
                            }
                        }
    
                        foreach (array ('ETT'=>'title', 'EAR'=>'artist', 'EAL'=>'album', 'INF'=>'comment', 'AUT'=>'author') as $key => $value) {
                            if (isset($parsed_lyrics3['raw'][$key])) {
                                $parsed_lyrics3['comments'][$value][] = trim($parsed_lyrics3['raw'][$key]);
                            }
                        }
    
                        if (isset($parsed_lyrics3['raw']['IMG'])) {
                            foreach (explode("\r\n", $parsed_lyrics3['raw']['IMG']) as $key => $image_string) {
                                if (strpos($image_string, '||') !== false) {
                                    $imagearray = explode('||', $image_string);
                                    $parsed_lyrics3['images'][$key]['filename']    = @$imagearray[0];
                                    $parsed_lyrics3['images'][$key]['description'] = @$imagearray[1];
                                    $parsed_lyrics3['images'][$key]['timestamp']   = getid3_lyrics3::Lyrics3Timestamp2Seconds(@$imagearray[2]);
                                }
                            }
                        }
                        
                        if (isset($parsed_lyrics3['raw']['LYR'])) {
                            getid3_lyrics3::Lyrics3LyricsTimestampParse($parsed_lyrics3);
                        }
                    }
                      else {
                        throw new getid3_exception('"LYRICS200" expected at '.(ftell($getid3->fp) - 11 + $lyrics3_size - 9).' but found "'.substr($raw_data, strlen($raw_data) - 9, 9).'" instead.');
                    }
                    break;
    
                default:
                    throw new getid3_exception('Cannot process Lyrics3 version '.$lyrics3_version.' (only v1 and v2)');
            }
    
            if (isset($getid3->info['id3v1']['tag_offset_start']) && ($getid3->info['id3v1']['tag_offset_start'] < $parsed_lyrics3['tag_offset_end'])) {
                $getid3->warning('ID3v1 tag information ignored since it appears to be a false synch in Lyrics3 tag data');
                unset($getid3->info['id3v1']);
            }
    
            $getid3->info['lyrics3'] = $parsed_lyrics3;
    
    
            // Check for APE tag after lyrics3
            if (!@$getid3->info['ape'] && $getid3->option_tag_apetag && class_exists('getid3_apetag')) {
                $apetag = new getid3_apetag($getid3);
                $apetag->option_override_end_offset = $getid3->info['lyrics3']['tag_offset_start'];
                $apetag->Analyze();
            }
        }

        return true;
    }
    

    
    
    public static function Lyrics3Timestamp2Seconds($rawtimestamp) {
        if (ereg('^\\[([0-9]{2}):([0-9]{2})\\]$', $rawtimestamp, $regs)) {
            return (int)(($regs[1] * 60) + $regs[2]);
        }
        return false;
    }

    
    
    public static function Lyrics3LyricsTimestampParse(&$lyrics3_data) {

        $lyrics_array = explode("\r\n", $lyrics3_data['raw']['LYR']);
        foreach ($lyrics_array as $key => $lyric_line) {
            
            while (ereg('^(\\[[0-9]{2}:[0-9]{2}\\])', $lyric_line, $regs)) {
                $this_line_timestamps[] = getid3_lyrics3::Lyrics3Timestamp2Seconds($regs[0]);
                $lyric_line = str_replace($regs[0], '', $lyric_line);
            }
            $no_timestamp_lyrics_array[$key] = $lyric_line;
            if (@is_array($this_line_timestamps)) {
                sort($this_line_timestamps);
                foreach ($this_line_timestamps as $timestampkey => $timestamp) {
                    if (isset($lyrics3_data['synchedlyrics'][$timestamp])) {
                        // timestamps only have a 1-second resolution, it's possible that multiple lines
                        // could have the same timestamp, if so, append
                        $lyrics3_data['synchedlyrics'][$timestamp] .= "\r\n".$lyric_line;
                    } else {
                        $lyrics3_data['synchedlyrics'][$timestamp] = $lyric_line;
                    }
                }
            }
            unset($this_line_timestamps);
            $regs = array ();
        }
        $lyrics3_data['unsynchedlyrics'] = implode("\r\n", $no_timestamp_lyrics_array);
        if (isset($lyrics3_data['synchedlyrics']) && is_array($lyrics3_data['synchedlyrics'])) {
            ksort($lyrics3_data['synchedlyrics']);
        }
        return true;
    }



    public static function IntString2Bool($char) {
        
        return $char == '1' ? true : ($char == '0' ? false : null);
    }
}


?>