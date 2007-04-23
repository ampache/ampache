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
// | module.audio-video.real.php                                          |
// | Module for analyzing Real Audio/Video files                          |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.real.php,v 1.4 2006/11/02 10:48:00 ah Exp $

        
        
class getid3_real extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');

        $getid3->info['fileformat']       = 'real';
        $getid3->info['bitrate']          = 0;
        $getid3->info['playtime_seconds'] = 0;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $chunk_counter = 0;

        while (ftell($getid3->fp) < $getid3->info['avdataend']) {
            
            $chunk_data = fread($getid3->fp, 8);
            $chunk_name =                           substr($chunk_data, 0, 4);
            $chunk_size = getid3_lib::BigEndian2Int(substr($chunk_data, 4, 4));

            if ($chunk_name == '.ra'."\xFD") {
                $chunk_data .= fread($getid3->fp, $chunk_size - 8);
                
                if ($this->ParseOldRAheader(substr($chunk_data, 0, 128), $getid3->info['real']['old_ra_header'])) {
                
                    $getid3->info['audio']['dataformat']      = 'real';
                    $getid3->info['audio']['lossless']        = false;
                    $getid3->info['audio']['sample_rate']     = $getid3->info['real']['old_ra_header']['sample_rate'];
                    $getid3->info['audio']['bits_per_sample'] = $getid3->info['real']['old_ra_header']['bits_per_sample'];
                    $getid3->info['audio']['channels']        = $getid3->info['real']['old_ra_header']['channels'];

                    $getid3->info['playtime_seconds']         = 60 * ($getid3->info['real']['old_ra_header']['audio_bytes'] / $getid3->info['real']['old_ra_header']['bytes_per_minute']);
                    $getid3->info['audio']['bitrate']         =  8 * ($getid3->info['real']['old_ra_header']['audio_bytes'] / $getid3->info['playtime_seconds']);
                    $getid3->info['audio']['codec']           = $this->RealAudioCodecFourCClookup($getid3->info['real']['old_ra_header']['fourcc'], $getid3->info['audio']['bitrate']);

                    foreach ($getid3->info['real']['old_ra_header']['comments'] as $key => $value_array) {
                
                        if (strlen(trim($value_array[0])) > 0) {
                            $getid3->info['real']['comments'][$key][] = trim($value_array[0]);
                        }
                    }
                    return true;
                }
                
                throw new getid3_exception('There was a problem parsing this RealAudio file. Please submit it for analysis to http://www.getid3.org/upload/ or info@getid3.org');
            }

            $getid3->info['real']['chunks'][$chunk_counter] = array ();
            $info_real_chunks_current_chunk = &$getid3->info['real']['chunks'][$chunk_counter];

            $info_real_chunks_current_chunk['name']   = $chunk_name;
            $info_real_chunks_current_chunk['offset'] = ftell($getid3->fp) - 8;
            $info_real_chunks_current_chunk['length'] = $chunk_size;
            
            if (($info_real_chunks_current_chunk['offset'] + $info_real_chunks_current_chunk['length']) > $getid3->info['avdataend']) {
                $getid3->warning('Chunk "'.$info_real_chunks_current_chunk['name'].'" at offset '.$info_real_chunks_current_chunk['offset'].' claims to be '.$info_real_chunks_current_chunk['length'].' bytes long, which is beyond end of file');
                return false;
            }

            if ($chunk_size > (getid3::FREAD_BUFFER_SIZE + 8)) {
                $chunk_data .= fread($getid3->fp, getid3::FREAD_BUFFER_SIZE - 8);
                fseek($getid3->fp, $info_real_chunks_current_chunk['offset'] + $chunk_size, SEEK_SET);

            } elseif(($chunk_size - 8) > 0) {
                $chunk_data .= fread($getid3->fp, $chunk_size - 8);
            }
            $offset = 8;

            switch ($chunk_name) {

                case '.RMF': // RealMedia File Header

                    $info_real_chunks_current_chunk['object_version'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                    $offset += 2;

                    switch ($info_real_chunks_current_chunk['object_version']) {

                        case 0:
                            $info_real_chunks_current_chunk['file_version']  = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 4));
                            $offset += 4;

                            $info_real_chunks_current_chunk['headers_count'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 4));
                            $offset += 4;
                            break;

                        default:
                            //$getid3->warning('Expected .RMF-object_version to be "0", actual value is "'.$info_real_chunks_current_chunk['object_version'].'" (should not be a problem)';
                            break;

                    }
                    break;


                case 'PROP': // Properties Header

                    $info_real_chunks_current_chunk['object_version']      = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                    $offset += 2;

                    if ($info_real_chunks_current_chunk['object_version'] == 0) {
                        
                        getid3_lib::ReadSequence('BigEndian2Int', $info_real_chunks_current_chunk, $chunk_data, $offset,
                            array (
                                'max_bit_rate'    => 4,
                                'avg_bit_rate'    => 4,
                                'max_packet_size' => 4,
                                'avg_packet_size' => 4,
                                'num_packets'     => 4,
                                'duration'        => 4,
                                'preroll'         => 4,
                                'index_offset'    => 4,
                                'data_offset'     => 4,
                                'num_streams'     => 2,
                                'flags_raw'       => 2
                            )
                        );
                        $offset += 40;

                        $getid3->info['playtime_seconds'] = $info_real_chunks_current_chunk['duration'] / 1000;
                        if ($info_real_chunks_current_chunk['duration'] > 0) {
                            $getid3->info['bitrate'] += $info_real_chunks_current_chunk['avg_bit_rate'];
                        }
                        
                        $info_real_chunks_current_chunk['flags']['save_enabled']   = (bool)($info_real_chunks_current_chunk['flags_raw'] & 0x0001);
                        $info_real_chunks_current_chunk['flags']['perfect_play']   = (bool)($info_real_chunks_current_chunk['flags_raw'] & 0x0002);
                        $info_real_chunks_current_chunk['flags']['live_broadcast'] = (bool)($info_real_chunks_current_chunk['flags_raw'] & 0x0004);
                    }
                    break;


                case 'MDPR': // Media Properties Header

                    $info_real_chunks_current_chunk['object_version']         = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                    $offset += 2;

                    if ($info_real_chunks_current_chunk['object_version'] == 0) {

                        getid3_lib::ReadSequence('BigEndian2Int', $info_real_chunks_current_chunk, $chunk_data, $offset,
                            array (
                                'stream_number'    => 2,
                                'max_bit_rate'     => 4,
                                'avg_bit_rate'     => 4,
                                'max_packet_size'  => 4,
                                'avg_packet_size'  => 4,
                                'start_time'       => 4,
                                'preroll'          => 4,
                                'duration'         => 4,
                                'stream_name_size' => 1
                            )
                        );
                        $offset += 31;
                        
                        $info_real_chunks_current_chunk['stream_name']        = substr($chunk_data, $offset, $info_real_chunks_current_chunk['stream_name_size']);
                        $offset += $info_real_chunks_current_chunk['stream_name_size'];
                        
                        $info_real_chunks_current_chunk['mime_type_size']     = getid3_lib::BigEndian2Int($chunk_data{$offset++});
                        
                        $info_real_chunks_current_chunk['mime_type']          = substr($chunk_data, $offset, $info_real_chunks_current_chunk['mime_type_size']);
                        $offset += $info_real_chunks_current_chunk['mime_type_size'];
                        
                        $info_real_chunks_current_chunk['type_specific_len']  = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 4));
                        $offset += 4;
                        
                        $info_real_chunks_current_chunk['type_specific_data'] = substr($chunk_data, $offset, $info_real_chunks_current_chunk['type_specific_len']);
                        $offset += $info_real_chunks_current_chunk['type_specific_len'];

                        $info_real_chunks_current_chunk_typespecificdata = &$info_real_chunks_current_chunk['type_specific_data'];

                        switch ($info_real_chunks_current_chunk['mime_type']) {
                          
                            case 'video/x-pn-realvideo':
                            case 'video/x-pn-multirate-realvideo':
                                // http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html

                                $info_real_chunks_current_chunk['video_info'] = array ();
                                $info_real_chunks_current_chunk_video_info    = &$info_real_chunks_current_chunk['video_info'];

                                getid3_lib::ReadSequence('BigEndian2Int', $info_real_chunks_current_chunk_video_info, $info_real_chunks_current_chunk_typespecificdata, 0,
                                    array (
                                        'dwSize'            => 4,
                                        'fourcc1'           => -4,
                                        'fourcc2'           => -4,
                                        'width'             => 2,
                                        'height'            => 2,
                                        'bits_per_sample'   => 2,
                                        'IGNORE-unknown1'   => 2,
                                        'IGNORE-unknown2'   => 2,
                                        'frames_per_second' => 2,
                                        'IGNORE-unknown3'   => 2,
                                        'IGNORE-unknown4'   => 2,
                                        'IGNORE-unknown5'   => 2,
                                        'IGNORE-unknown6'   => 2,
                                        'IGNORE-unknown7'   => 2,
                                        'IGNORE-unknown8'   => 2,
                                        'IGNORE-unknown9'   => 2
                                    )
                                );

                                $info_real_chunks_current_chunk_video_info['codec'] = getid3_riff::RIFFfourccLookup($info_real_chunks_current_chunk_video_info['fourcc2']);

                                $getid3->info['video']['resolution_x']    =        $info_real_chunks_current_chunk_video_info['width'];
                                $getid3->info['video']['resolution_y']    =        $info_real_chunks_current_chunk_video_info['height'];
                                $getid3->info['video']['frame_rate']      = (float)$info_real_chunks_current_chunk_video_info['frames_per_second'];
                                $getid3->info['video']['codec']           =        $info_real_chunks_current_chunk_video_info['codec'];
                                $getid3->info['video']['bits_per_sample'] =        $info_real_chunks_current_chunk_video_info['bits_per_sample'];
                                break;


                            case 'audio/x-pn-realaudio':
                            case 'audio/x-pn-multirate-realaudio':

                                $this->ParseOldRAheader($info_real_chunks_current_chunk_typespecificdata, $info_real_chunks_current_chunk['parsed_audio_data']);

                                $getid3->info['audio']['sample_rate']     = $info_real_chunks_current_chunk['parsed_audio_data']['sample_rate'];
                                $getid3->info['audio']['bits_per_sample'] = $info_real_chunks_current_chunk['parsed_audio_data']['bits_per_sample'];
                                $getid3->info['audio']['channels']        = $info_real_chunks_current_chunk['parsed_audio_data']['channels'];

                                if (!empty($getid3->info['audio']['dataformat'])) {
                                    foreach ($getid3->info['audio'] as $key => $value) {
                                        if ($key != 'streams') {
                                            $getid3->info['audio']['streams'][$info_real_chunks_current_chunk['stream_number']][$key] = $value;
                                        }
                                    }
                                }
                                break;


                            case 'logical-fileinfo':

                                $info_real_chunks_current_chunk['logical_fileinfo']['logical_fileinfo_length'] = getid3_lib::BigEndian2Int(substr($info_real_chunks_current_chunk_typespecificdata, 0, 4));
                                // $info_real_chunks_current_chunk['logical_fileinfo']['IGNORE-unknown1']             = getid3_lib::BigEndian2Int(substr($info_real_chunks_current_chunk_typespecificdata, 4, 4));
                                $info_real_chunks_current_chunk['logical_fileinfo']['num_tags']                = getid3_lib::BigEndian2Int(substr($info_real_chunks_current_chunk_typespecificdata, 8, 4));
                                // $info_real_chunks_current_chunk['logical_fileinfo']['IGNORE-unknown2']             = getid3_lib::BigEndian2Int(substr($info_real_chunks_current_chunk_typespecificdata, 12, 4));
                                break;

                        }


                        if (empty($getid3->info['playtime_seconds'])) {
                            $getid3->info['playtime_seconds'] = max($getid3->info['playtime_seconds'], ($info_real_chunks_current_chunk['duration'] + $info_real_chunks_current_chunk['start_time']) / 1000);
                        }
                        
                        if ($info_real_chunks_current_chunk['duration'] > 0) {
                        
                            switch ($info_real_chunks_current_chunk['mime_type']) {
                        
                                case 'audio/x-pn-realaudio':
                                case 'audio/x-pn-multirate-realaudio':
                                    
                                    $getid3->info['audio']['bitrate']    = (isset($getid3->info['audio']['bitrate']) ? $getid3->info['audio']['bitrate'] : 0) + $info_real_chunks_current_chunk['avg_bit_rate'];
                                    $getid3->info['audio']['codec']      = $this->RealAudioCodecFourCClookup($info_real_chunks_current_chunk['parsed_audio_data']['fourcc'], $getid3->info['audio']['bitrate']);
                                    $getid3->info['audio']['dataformat'] = 'real';
                                    $getid3->info['audio']['lossless']   = false;
                                    break;


                                case 'video/x-pn-realvideo':
                                case 'video/x-pn-multirate-realvideo':

                                    $getid3->info['video']['bitrate']            = (isset($getid3->info['video']['bitrate']) ? $getid3->info['video']['bitrate'] : 0) + $info_real_chunks_current_chunk['avg_bit_rate'];
                                    $getid3->info['video']['bitrate_mode']       = 'cbr';
                                    $getid3->info['video']['dataformat']         = 'real';
                                    $getid3->info['video']['lossless']           = false;
                                    $getid3->info['video']['pixel_aspect_ratio'] = (float)1;
                                    break;


                                case 'audio/x-ralf-mpeg4-generic':

                                    $getid3->info['audio']['bitrate']    = (isset($getid3->info['audio']['bitrate']) ? $getid3->info['audio']['bitrate'] : 0) + $info_real_chunks_current_chunk['avg_bit_rate'];
                                    $getid3->info['audio']['codec']      = 'RealAudio Lossless';
                                    $getid3->info['audio']['dataformat'] = 'real';
                                    $getid3->info['audio']['lossless']   = true;
                                    break;
                                    
                            }
                            
                            $getid3->info['bitrate'] = (isset($getid3->info['video']['bitrate']) ? $getid3->info['video']['bitrate'] : 0) + (isset($getid3->info['audio']['bitrate']) ? $getid3->info['audio']['bitrate'] : 0);
                        }
                    }
                    break;


                case 'CONT': // Content Description Header (text comments)

                    $info_real_chunks_current_chunk['object_version'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                    $offset += 2;

                    if ($info_real_chunks_current_chunk['object_version'] == 0) {

                        $info_real_chunks_current_chunk['title_len'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                        $offset += 2;

                        $info_real_chunks_current_chunk['title'] = (string) substr($chunk_data, $offset, $info_real_chunks_current_chunk['title_len']);
                        $offset += $info_real_chunks_current_chunk['title_len'];

                        $info_real_chunks_current_chunk['artist_len'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                        $offset += 2;

                        $info_real_chunks_current_chunk['artist'] = (string) substr($chunk_data, $offset, $info_real_chunks_current_chunk['artist_len']);
                        $offset += $info_real_chunks_current_chunk['artist_len'];

                        $info_real_chunks_current_chunk['copyright_len'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                        $offset += 2;

                        $info_real_chunks_current_chunk['copyright'] = (string) substr($chunk_data, $offset, $info_real_chunks_current_chunk['copyright_len']);
                        $offset += $info_real_chunks_current_chunk['copyright_len'];

                        $info_real_chunks_current_chunk['comment_len'] = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                        $offset += 2;

                        $info_real_chunks_current_chunk['comment'] = (string) substr($chunk_data, $offset, $info_real_chunks_current_chunk['comment_len']);
                        $offset += $info_real_chunks_current_chunk['comment_len'];

                        foreach (array ('title'=>'title', 'artist'=>'artist', 'copyright'=>'copyright', 'comment'=>'comment') as $key => $val) {
                            if ($info_real_chunks_current_chunk[$key]) {
                                $getid3->info['real']['comments'][$val][] = trim($info_real_chunks_current_chunk[$key]);
                            }
                        }
                    }
                    break;


                case 'DATA': // Data Chunk Header

                    // do nothing
                    break;


                case 'INDX': // Index Section Header

                    $info_real_chunks_current_chunk['object_version']        = getid3_lib::BigEndian2Int(substr($chunk_data, $offset, 2));
                    $offset += 2;

                    if ($info_real_chunks_current_chunk['object_version'] == 0) {

                        getid3_lib::ReadSequence('BigEndian2Int', $info_real_chunks_current_chunk, $chunk_data, $offset, 
                            array (
                                'num_indices'       => 4,
                                'stream_number'     => 2,
                                'next_index_header' => 4
                            )
                        );
                        $offset += 10;

                        if ($info_real_chunks_current_chunk['next_index_header'] == 0) {
                            // last index chunk found, ignore rest of file
                            break 2;
                        } else {
                            // non-last index chunk, seek to next index chunk (skipping actual index data)
                            fseek($getid3->fp, $info_real_chunks_current_chunk['next_index_header'], SEEK_SET);
                        }
                    }
                    break;


                default:
                    $getid3->warning('Unhandled RealMedia chunk "'.$chunk_name.'" at offset '.$info_real_chunks_current_chunk['offset']);
                    break;
            }
            $chunk_counter++;
        }

        if (!empty($getid3->info['audio']['streams'])) {
            
            $getid3->info['audio']['bitrate'] = 0;
            
            foreach ($getid3->info['audio']['streams'] as $key => $value_array) {
                $getid3->info['audio']['bitrate'] += $value_array['bitrate'];
            }
        }

        return true;
    }



    public static function ParseOldRAheader($old_ra_header_data, &$parsed_array) {

        // http://www.freelists.org/archives/matroska-devel/07-2003/msg00010.html

        $parsed_array = array ();
        $parsed_array['magic'] = substr($old_ra_header_data, 0, 4);
        
        if ($parsed_array['magic'] != '.ra'."\xFD") {
            return false;
        }
        
        $parsed_array['version1'] = getid3_lib::BigEndian2Int(substr($old_ra_header_data,  4, 2));

        if ($parsed_array['version1'] < 3) {

            return false;
        } 

        if ($parsed_array['version1'] == 3) {

            $parsed_array['fourcc1']          = '.ra3';
            $parsed_array['bits_per_sample']  = 16;   // hard-coded for old versions?
            $parsed_array['sample_rate']      = 8000; // hard-coded for old versions?

            getid3_lib::ReadSequence('BigEndian2Int', $parsed_array, $old_ra_header_data, 6,
                array (
                    'header_size'      => 2,
                    'channels'         => 2, // always 1 (?)
                    'IGNORE-unknown1'  => 2,
                    'IGNORE-unknown2'  => 2,
                    'IGNORE-unknown3'  => 2,
                    'bytes_per_minute' => 2,
                    'audio_bytes'      => 4,
                )
            );
            
            $parsed_array['comments_raw'] = substr($old_ra_header_data, 22, $parsed_array['header_size'] - 22 + 1); // not including null terminator

            $comment_offset = 0;
            
            foreach (array ('title', 'artist', 'copyright') as $name) {
                $comment_length = getid3_lib::BigEndian2Int($parsed_array['comments_raw']{$comment_offset++});
                $parsed_array['comments'][$name][]= substr($parsed_array['comments_raw'], $comment_offset, $comment_length);
                $comment_offset += $comment_length;
            }
    
            $comment_offset++; // final null terminator (?)
            $comment_offset++; // fourcc length (?) should be 4
            
            $parsed_array['fourcc'] = substr($old_ra_header_data, 23 + $comment_offset, 4);


        } elseif ($parsed_array['version1'] <= 5) {

            getid3_lib::ReadSequence('BigEndian2Int', $parsed_array, $old_ra_header_data, 6,
                array (
                    'IGNORE-unknown1'  => 2,
                    'fourcc1'          => -4,
                    'file_size'        => 4,
                    'version2'         => 2,
                    'header_size'      => 4,
                    'codec_flavor_id'  => 2,
                    'coded_frame_size' => 4,
                    'audio_bytes'      => 4,
                    'bytes_per_minute' => 4,
                    'IGNORE-unknown5'  => 4,
                    'sub_packet_h'     => 2,
                    'frame_size'       => 2,
                    'sub_packet_size'  => 2,
                    'IGNORE-unknown6'  => 2
                )
            );

            switch ($parsed_array['version1']) {

                case 4:
             
                    getid3_lib::ReadSequence('BigEndian2Int', $parsed_array, $old_ra_header_data, 48,
                        array (
                            'sample_rate'      => 2,
                            'IGNORE-unknown8'  => 2,
                            'bits_per_sample'  => 2,
                            'channels'         => 2,
                            'length_fourcc2'   => 1,
                            'fourcc2'          => -4,
                            'length_fourcc3'   => 1,
                            'fourcc3'          => -4,
                            'IGNORE-unknown9'  => 1,
                            'IGNORE-unknown10' => 2,
                        )
                    );

                    $parsed_array['comments_raw'] = substr($old_ra_header_data, 69, $parsed_array['header_size'] - 69 + 16);

                    $comment_offset = 0;
                    
                    foreach (array ('title', 'artist', 'copyright') as $name) {
                        $comment_length = getid3_lib::BigEndian2Int($parsed_array['comments_raw']{$comment_offset++});
                        $parsed_array['comments'][$name][]= substr($parsed_array['comments_raw'], $comment_offset, $comment_length);
                        $comment_offset += $comment_length;
                    }
                    break;


                case 5:
                
                getid3_lib::ReadSequence('BigEndian2Int', $parsed_array, $old_ra_header_data, 48,
                        array (
                            'sample_rate'      => 4,
                            'sample_rate2'     => 4,
                            'bits_per_sample'  => 4,
                            'channels'         => 2,
                            'genr'             => -4,
                            'fourcc3'          => -4,
                        )
                    );
                    $parsed_array['comments'] = array ();
                    break;
                    
            }
            
            $parsed_array['fourcc'] = $parsed_array['fourcc3'];

        }

        foreach ($parsed_array['comments'] as $key => $value) {
            
            if ($parsed_array['comments'][$key][0] === false) {
                $parsed_array['comments'][$key][0] = '';
            }
        }

        return true;
    }



    public static function RealAudioCodecFourCClookup($fourcc, $bitrate) {

        // http://www.its.msstate.edu/net/real/reports/config/tags.stats             
        // http://www.freelists.org/archives/matroska-devel/06-2003/fullthread18.html
        
        static $lookup;
        
        if (empty($lookup)) {
            $lookup['14_4'][8000]  = 'RealAudio v2 (14.4kbps)';
            $lookup['14.4'][8000]  = 'RealAudio v2 (14.4kbps)';
            $lookup['lpcJ'][8000]  = 'RealAudio v2 (14.4kbps)';
            $lookup['28_8'][15200] = 'RealAudio v2 (28.8kbps)';
            $lookup['28.8'][15200] = 'RealAudio v2 (28.8kbps)';
            $lookup['sipr'][4933]  = 'RealAudio v4 (5kbps Voice)';
            $lookup['sipr'][6444]  = 'RealAudio v4 (6.5kbps Voice)';
            $lookup['sipr'][8444]  = 'RealAudio v4 (8.5kbps Voice)';
            $lookup['sipr'][16000] = 'RealAudio v4 (16kbps Wideband)';
            $lookup['dnet'][8000]  = 'RealAudio v3 (8kbps Music)';
            $lookup['dnet'][16000] = 'RealAudio v3 (16kbps Music Low Response)';
            $lookup['dnet'][15963] = 'RealAudio v3 (16kbps Music Mid/High Response)';
            $lookup['dnet'][20000] = 'RealAudio v3 (20kbps Music Stereo)';
            $lookup['dnet'][32000] = 'RealAudio v3 (32kbps Music Mono)';
            $lookup['dnet'][31951] = 'RealAudio v3 (32kbps Music Stereo)';
            $lookup['dnet'][39965] = 'RealAudio v3 (40kbps Music Mono)';
            $lookup['dnet'][40000] = 'RealAudio v3 (40kbps Music Stereo)';
            $lookup['dnet'][79947] = 'RealAudio v3 (80kbps Music Mono)';
            $lookup['dnet'][80000] = 'RealAudio v3 (80kbps Music Stereo)';

            $lookup['dnet'][0] = 'RealAudio v3';
            $lookup['sipr'][0] = 'RealAudio v4';
            $lookup['cook'][0] = 'RealAudio G2';
            $lookup['atrc'][0] = 'RealAudio 8';
        }
        
        $round_bitrate = intval(round($bitrate));
        
        if (isset($lookup[$fourcc][$round_bitrate])) {
            return $lookup[$fourcc][$round_bitrate];
        }
            
        if (isset($lookup[$fourcc][0])) {
            return $lookup[$fourcc][0];
        }
        
        return $fourcc;
    }

}


?>