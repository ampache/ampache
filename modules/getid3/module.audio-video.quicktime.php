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
// | module.audio-video.quicktime.php                                     |
// | Module for analyzing Quicktime, MP3-in-MP4 and Apple Lossless files. |
// | dependencies: module.audio.mp3.php                                   |
// |               zlib support in PHP (optional)                         |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.quicktime.php,v 1.7 2006/11/02 16:03:28 ah Exp $

        
        
class getid3_quicktime extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;

        $info   = &$getid3->info;
        
        $getid3->include_module('audio.mp3');
        
        $info['quicktime'] = array ();
        $info_quicktime = &$info['quicktime'];

        $info['fileformat'] = 'quicktime';
        $info_quicktime['hinting'] = false;

        fseek($getid3->fp, $info['avdataoffset'], SEEK_SET);

        $offset = $atom_counter = 0;

        while ($offset < $info['avdataend']) {

            fseek($getid3->fp, $offset, SEEK_SET);
            $atom_header = fread($getid3->fp, 8);

            $atom_size = getid3_lib::BigEndian2Int(substr($atom_header, 0, 4));
            $atom_name =               substr($atom_header, 4, 4);
            
            $info_quicktime[$atom_name]['name']   = $atom_name;
            $info_quicktime[$atom_name]['size']   = $atom_size;
            $info_quicktime[$atom_name]['offset'] = $offset;

            if (($offset + $atom_size) > $info['avdataend']) {
                throw new getid3_exception('Atom at offset '.$offset.' claims to go beyond end-of-file (length: '.$atom_size.' bytes)');
            }

            if ($atom_size == 0) {
                // Furthermore, for historical reasons the list of atoms is optionally
                // terminated by a 32-bit integer set to 0. If you are writing a program
                // to read user data atoms, you should allow for the terminating 0.
                break;
            }
            
            switch ($atom_name) {
            
                case 'mdat': // Media DATa atom
                    // 'mdat' contains the actual data for the audio/video
                    if (($atom_size > 8) && (!isset($info['avdataend_tmp']) || ($info_quicktime[$atom_name]['size'] > ($info['avdataend_tmp'] - $info['avdataoffset'])))) {

                        $info['avdataoffset'] = $info_quicktime[$atom_name]['offset'] + 8;
                        $old_av_data_end      = $info['avdataend'];
                        $info['avdataend']    = $info_quicktime[$atom_name]['offset'] + $info_quicktime[$atom_name]['size'];

                        
                        //// MP3
                        
                        if (!$getid3->include_module_optional('audio.mp3')) {
                           $getid3->warning('MP3 skipped because mpeg module is missing.');
                        }
                                                                    
                        else {
                            
                            // Clone getid3 - messing with offsets - better safe than sorry
                            $clone = clone $getid3;
                            
                            if (getid3_mp3::MPEGaudioHeaderValid(getid3_mp3::MPEGaudioHeaderDecode(fread($clone->fp, 4)))) {
                            
                                $mp3 = new getid3_mp3($clone);
                                $mp3->AnalyzeMPEGaudioInfo();
                                
                                // Import from clone and destroy
                                if (isset($clone->info['mpeg']['audio'])) {
                                
                                    $info['mpeg']['audio'] = $clone->info['mpeg']['audio'];
                                
                                    $info['audio']['dataformat']   = 'mp3';
                                    $info['audio']['codec']        = (!empty($info['mpeg']['audio']['encoder']) ? $info['mpeg']['audio']['encoder'] : (!empty($info['mpeg']['audio']['codec']) ? $info['mpeg']['audio']['codec'] : (!empty($info['mpeg']['audio']['LAME']) ? 'LAME' :'mp3')));
                                    $info['audio']['sample_rate']  = $info['mpeg']['audio']['sample_rate'];
                                    $info['audio']['channels']     = $info['mpeg']['audio']['channels'];
                                    $info['audio']['bitrate']      = $info['mpeg']['audio']['bitrate'];
                                    $info['audio']['bitrate_mode'] = strtolower($info['mpeg']['audio']['bitrate_mode']);
                                    $info['bitrate']               = $info['audio']['bitrate'];
                                    
                                    $getid3->warning($clone->warnings());
                                    unset($clone);
                                }
                            }
                        }
                        
                        $info['avdataend'] = $old_av_data_end;
                        unset($old_av_data_end);

                    }
                    break;
                    

                case 'free': // FREE space atom
                case 'skip': // SKIP atom
                case 'wide': // 64-bit expansion placeholder atom
                    // 'free', 'skip' and 'wide' are just padding, contains no useful data at all
                    break;


                default:
                    $atom_hierarchy = array ();
                    $info_quicktime[$atom_name] = $this->QuicktimeParseAtom($atom_name, $atom_size, fread($getid3->fp, $atom_size), $offset, $atom_hierarchy);
                    break;
            }

            $offset += $atom_size;
            $atom_counter++;
        }

        if (!empty($info['avdataend_tmp'])) {
            // this value is assigned to a temp value and then erased because
            // otherwise any atoms beyond the 'mdat' atom would not get parsed
            $info['avdataend'] = $info['avdataend_tmp'];
            unset($info['avdataend_tmp']);
        }

        if (!isset($info['bitrate']) && isset($info['playtime_seconds'])) {
            $info['bitrate'] = (($info['avdataend'] - $info['avdataoffset']) * 8) / $info['playtime_seconds'];
        }
        
        if (isset($info['bitrate']) && !isset($info['audio']['bitrate']) && !isset($info_quicktime['video'])) {
            $info['audio']['bitrate'] = $info['bitrate'];
        }

        if ((@$info['audio']['dataformat'] == 'mp4') && empty($info['video']['resolution_x'])) {
            $info['fileformat'] = 'mp4';
            $info['mime_type']  = 'audio/mp4';
            unset($info['video']['dataformat']);
        }

        if (!$getid3->option_extra_info) {
            unset($info_quicktime['moov']);
        }

        if (empty($info['audio']['dataformat']) && !empty($info_quicktime['audio'])) {
            $info['audio']['dataformat'] = 'quicktime';
        }
        
        if (empty($info['video']['dataformat']) && !empty($info_quicktime['video'])) {
            $info['video']['dataformat'] = 'quicktime';
        }

        return true;
    }



    private function QuicktimeParseAtom($atom_name, $atom_size, $atom_data, $base_offset, &$atom_hierarchy) {
        
        // http://developer.apple.com/techpubs/quicktime/qtdevdocs/APIREF/INDEX/atomalphaindex.htm
        
        $getid3 = $this->getid3;
        
        $info           = &$getid3->info;
        $info_quicktime = &$info['quicktime'];

        array_push($atom_hierarchy, $atom_name);
        $atom_structure['hierarchy'] = implode(' ', $atom_hierarchy);
        $atom_structure['name']      = $atom_name;
        $atom_structure['size']      = $atom_size;
        $atom_structure['offset']    = $base_offset;

        switch ($atom_name) {
            case 'moov': // MOVie container atom
            case 'trak': // TRAcK container atom
            case 'clip': // CLIPping container atom
            case 'matt': // track MATTe container atom
            case 'edts': // EDiTS container atom
            case 'tref': // Track REFerence container atom
            case 'mdia': // MeDIA container atom
            case 'minf': // Media INFormation container atom
            case 'dinf': // Data INFormation container atom
            case 'udta': // User DaTA container atom
            case 'stbl': // Sample TaBLe container atom
            case 'cmov': // Compressed MOVie container atom
            case 'rmra': // Reference Movie Record Atom
            case 'rmda': // Reference Movie Descriptor Atom
            case 'gmhd': // Generic Media info HeaDer atom (seen on QTVR)
                $atom_structure['subatoms'] = $this->QuicktimeParseContainerAtom($atom_data, $base_offset + 8, $atom_hierarchy);
                break;


            case '©cpy':
            case '©day':
            case '©dir':
            case '©ed1':
            case '©ed2':
            case '©ed3':
            case '©ed4':
            case '©ed5':
            case '©ed6':
            case '©ed7':
            case '©ed8':
            case '©ed9':
            case '©fmt':
            case '©inf':
            case '©prd':
            case '©prf':
            case '©req':
            case '©src':
            case '©wrt':
            case '©nam':
            case '©cmt':
            case '©wrn':
            case '©hst':
            case '©mak':
            case '©mod':
            case '©PRD':
            case '©swr':
            case '©aut':
            case '©ART':
            case '©trk':
            case '©alb':
            case '©com':
            case '©gen':
            case '©ope':
            case '©url':
            case '©enc':
                $atom_structure['data_length'] = getid3_lib::BigEndian2Int(substr($atom_data,  0, 2));
				$atom_structure['language_id'] = getid3_lib::BigEndian2Int(substr($atom_data,  2, 2));
				$atom_structure['data']        =                           substr($atom_data,  4);

                $atom_structure['language']    = $this->QuicktimeLanguageLookup($atom_structure['language_id']);
                if (empty($info['comments']['language']) || (!in_array($atom_structure['language'], $info['comments']['language']))) {
                    $info['comments']['language'][] = $atom_structure['language'];
                }
                $this->CopyToAppropriateCommentsSection($atom_name, $atom_structure['data']);
                break;


            case 'play': // auto-PLAY atom
                $atom_structure['autoplay'] = (bool)getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));

                $info_quicktime['autoplay'] = $atom_structure['autoplay'];
                break;


            case 'WLOC': // Window LOCation atom
                $atom_structure['location_x'] = getid3_lib::BigEndian2Int(substr($atom_data,  0, 2));
                $atom_structure['location_y'] = getid3_lib::BigEndian2Int(substr($atom_data,  2, 2));
                break;


            case 'LOOP': // LOOPing atom
            case 'SelO': // play SELection Only atom
            case 'AllF': // play ALL Frames atom
                $atom_structure['data'] = getid3_lib::BigEndian2Int($atom_data);
                break;


            case 'name': //
            case 'MCPS': // Media Cleaner PRo
            case '@PRM': // adobe PReMiere version
            case '@PRQ': // adobe PRemiere Quicktime version
                $atom_structure['data'] = $atom_data;
                break;


            case 'cmvd': // Compressed MooV Data atom
                // Code by ubergeekØubergeek*tv based on information from
                // http://developer.apple.com/quicktime/icefloe/dispatch012.html
                $atom_structure['unCompressedSize'] = getid3_lib::BigEndian2Int(substr($atom_data, 0, 4));

                $compressed_file_data = substr($atom_data, 4);
                if (!function_exists('gzuncompress'))  {
                    $getid3->warning('PHP does not have zlib support - cannot decompress MOV atom at offset '.$atom_structure['offset']);
                }
                elseif ($uncompressed_header = @gzuncompress($compressed_file_data)) {
                    $atom_structure['subatoms'] = $this->QuicktimeParseContainerAtom($uncompressed_header, 0, $atom_hierarchy);
                } else {
                    $getid3->warning('Error decompressing compressed MOV atom at offset '.$atom_structure['offset']);
                }
                break;


            case 'dcom': // Data COMpression atom
                $atom_structure['compression_id']   = $atom_data;
                $atom_structure['compression_text'] = getid3_quicktime::QuicktimeDCOMLookup($atom_data);
                break;


            case 'rdrf': // Reference movie Data ReFerence atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'             => 1, 
                        'flags_raw'           => 3, 
                        'reference_type_name' => -4,
                        'reference_length'    => 4, 
                    )
                );
                
                $atom_structure['flags']['internal_data'] = (bool)($atom_structure['flags_raw'] & 0x000001);
                
                switch ($atom_structure['reference_type_name']) {
                    case 'url ':
                        $atom_structure['url']            = $this->NoNullString(substr($atom_data, 12));
                        break;

                    case 'alis':
                        $atom_structure['file_alias']     =                     substr($atom_data, 12);
                        break;

                    case 'rsrc':
                        $atom_structure['resource_alias'] =                     substr($atom_data, 12);
                        break;

                    default:
                        $atom_structure['data']           =                     substr($atom_data, 12);
                        break;
                }
                break;


            case 'rmqu': // Reference Movie QUality atom
                $atom_structure['movie_quality'] = getid3_lib::BigEndian2Int($atom_data);
                break;


            case 'rmcs': // Reference Movie Cpu Speed atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'          => 1,
                        'flags_raw'        => 3, // hardcoded: 0x0000
                        'cpu_speed_rating' => 2
                    )
                );
                break;


            case 'rmvc': // Reference Movie Version Check atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'            => 1,
                        'flags_raw'          => 3, // hardcoded: 0x0000
                        'gestalt_selector'   => -4,
                        'gestalt_value_mask' => 4,
                        'gestalt_value'      => 4,
                        'gestalt_check_type' => 2
                    )
                );
                break;


            case 'rmcd': // Reference Movie Component check atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'                => 1,
                        'flags_raw'              => 3, // hardcoded: 0x0000
                        'component_type'         => -4,
                        'component_subtype'      => -4,
                        'component_manufacturer' => -4,
                        'component_flags_raw'    => 4,
                        'component_flags_mask'   => 4,
                        'component_min_version'  => 4
                    )
                );
                break;


            case 'rmdr': // Reference Movie Data Rate atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'   => 1,
                        'flags_raw' => 3, // hardcoded: 0x0000
                        'data_rate' => 4
                    )
                );

                $atom_structure['data_rate_bps'] = $atom_structure['data_rate'] * 10;
                break;


            case 'rmla': // Reference Movie Language Atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'     => 1,
                        'flags_raw'   => 3, // hardcoded: 0x0000
                        'language_id' => 2
                    )
                );

                $atom_structure['language']    = $this->QuicktimeLanguageLookup($atom_structure['language_id']);
                if (empty($info['comments']['language']) || (!in_array($atom_structure['language'], $info['comments']['language']))) {
                    $info['comments']['language'][] = $atom_structure['language'];
                }
                break;


            case 'rmla': // Reference Movie Language Atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'   => 1,
                        'flags_raw' => 3, // hardcoded: 0x0000
                        'track_id'  => 2
                    )
                );
                break;


            case 'ptv ': // Print To Video - defines a movie's full screen mode
                // http://developer.apple.com/documentation/QuickTime/APIREF/SOURCESIV/at_ptv-_pg.htm
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'display_size_raw'  => 2,
                        'reserved_1'        => 2, // hardcoded: 0x0000
                        'reserved_2'        => 2, // hardcoded: 0x0000
                        'slide_show_flag'   => 1,
                        'play_on_open_flag' => 1
                    )
                );

                $atom_structure['flags']['play_on_open'] = (bool)$atom_structure['play_on_open_flag'];
                $atom_structure['flags']['slide_show']   = (bool)$atom_structure['slide_show_flag'];

                $ptv_lookup[0] = 'normal';
                $ptv_lookup[1] = 'double';
                $ptv_lookup[2] = 'half';
                $ptv_lookup[3] = 'full';
                $ptv_lookup[4] = 'current';
                if (isset($ptv_lookup[$atom_structure['display_size_raw']])) {
                    $atom_structure['display_size'] = $ptv_lookup[$atom_structure['display_size_raw']];
                } else {
                    $getid3->warning('unknown "ptv " display constant ('.$atom_structure['display_size_raw'].')');
                }
                break;


            case 'stsd': // Sample Table Sample Description atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'        => 1,
                        'flags_raw'      => 3, // hardcoded: 0x0000
                        'number_entries' => 4
                    )
                );
                $stsd_entries_data_offset = 8;
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                    
                    getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['sample_description_table'][$i], $atom_data, $stsd_entries_data_offset, 
                        array (
                            'size'            => 4,
                            'data_format'     => -4,
                            'reserved'        => 6,
                            'reference_index' => 2
                        )
                    );

                    $atom_structure['sample_description_table'][$i]['data'] = substr($atom_data, 16+$stsd_entries_data_offset, ($atom_structure['sample_description_table'][$i]['size'] - 4 - 4 - 6 - 2));
                    $stsd_entries_data_offset += 16 + ($atom_structure['sample_description_table'][$i]['size'] - 4 - 4 - 6 - 2);

                    getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['sample_description_table'][$i], $atom_structure['sample_description_table'][$i]['data'], 0,
                        array (
                            'encoder_version'  => 2,
                            'encoder_revision' => 2,
                            'encoder_vendor'   => -4
                        )
                    );

                    switch ($atom_structure['sample_description_table'][$i]['encoder_vendor']) {

                        case "\x00\x00\x00\x00":
                            // audio atom
                            getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['sample_description_table'][$i], $atom_structure['sample_description_table'][$i]['data'], 8,
                                array (
                                    'audio_channels'       => 2,
                                    'audio_bit_depth'      => 2,
                                    'audio_compression_id' => 2,
                                    'audio_packet_size'    => 2
                                )
                            );
                            
                            $atom_structure['sample_description_table'][$i]['audio_sample_rate'] = getid3_quicktime::FixedPoint16_16(substr($atom_structure['sample_description_table'][$i]['data'], 16, 4));

                            switch ($atom_structure['sample_description_table'][$i]['data_format']) {

                                case 'mp4v':
                                    $info['fileformat'] = 'mp4';
                                    throw new getid3_exception('This version of getID3() does not fully support MPEG-4 audio/video streams');

                                case 'qtvr':
                                    $info['video']['dataformat'] = 'quicktimevr';
                                    break;

                                case 'mp4a':
                                default:
                                    $info_quicktime['audio']['codec']       = $this->QuicktimeAudioCodecLookup($atom_structure['sample_description_table'][$i]['data_format']);
                                    $info_quicktime['audio']['sample_rate'] = $atom_structure['sample_description_table'][$i]['audio_sample_rate'];
                                    $info_quicktime['audio']['channels']    = $atom_structure['sample_description_table'][$i]['audio_channels'];
                                    $info_quicktime['audio']['bit_depth']   = $atom_structure['sample_description_table'][$i]['audio_bit_depth'];
                                    $info['audio']['codec']                 = $info_quicktime['audio']['codec'];
                                    $info['audio']['sample_rate']           = $info_quicktime['audio']['sample_rate'];
                                    $info['audio']['channels']              = $info_quicktime['audio']['channels'];
                                    $info['audio']['bits_per_sample']       = $info_quicktime['audio']['bit_depth'];
                                    switch ($atom_structure['sample_description_table'][$i]['data_format']) {
                                        case 'raw ': // PCM
                                        case 'alac': // Apple Lossless Audio Codec
                                            $info['audio']['lossless'] = true;
                                            break;
                                        default:
                                            $info['audio']['lossless'] = false;
                                            break;
                                    }
                                    break;
                            }
                            break;

                        default:
                            switch ($atom_structure['sample_description_table'][$i]['data_format']) {
                                case 'mp4s':
                                    $info['fileformat'] = 'mp4';
                                    break;

                                default:
                                    // video atom
                                    getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['sample_description_table'][$i], $atom_structure['sample_description_table'][$i]['data'], 8,
                                        array (
                                            'video_temporal_quality' => 4,
                                            'video_spatial_quality'  => 4,
                                            'video_frame_width'      => 2,
                                            'video_frame_height'     => 2
                                        )
                                    );
                                    $atom_structure['sample_description_table'][$i]['video_resolution_x']      = getid3_quicktime::FixedPoint16_16(substr($atom_structure['sample_description_table'][$i]['data'], 20,  4));
                                    $atom_structure['sample_description_table'][$i]['video_resolution_y']      = getid3_quicktime::FixedPoint16_16(substr($atom_structure['sample_description_table'][$i]['data'], 24,  4));
                                    getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['sample_description_table'][$i], $atom_structure['sample_description_table'][$i]['data'], 28,
                                        array (                                        
                                            'video_data_size'        => 4,
                                            'video_frame_count'      => 2,
                                            'video_encoder_name_len' => 1
                                        )
                                    );
                                    $atom_structure['sample_description_table'][$i]['video_encoder_name']      = substr($atom_structure['sample_description_table'][$i]['data'], 35, $atom_structure['sample_description_table'][$i]['video_encoder_name_len']);
                                    $atom_structure['sample_description_table'][$i]['video_pixel_color_depth'] = getid3_lib::BigEndian2Int(substr($atom_structure['sample_description_table'][$i]['data'], 66,  2));
                                    $atom_structure['sample_description_table'][$i]['video_color_table_id']    = getid3_lib::BigEndian2Int(substr($atom_structure['sample_description_table'][$i]['data'], 68,  2));

                                    $atom_structure['sample_description_table'][$i]['video_pixel_color_type']  = (($atom_structure['sample_description_table'][$i]['video_pixel_color_depth'] > 32) ? 'grayscale' : 'color');
                                    $atom_structure['sample_description_table'][$i]['video_pixel_color_name']  = $this->QuicktimeColorNameLookup($atom_structure['sample_description_table'][$i]['video_pixel_color_depth']);

                                    if ($atom_structure['sample_description_table'][$i]['video_pixel_color_name'] != 'invalid') {
                                        $info_quicktime['video']['codec_fourcc']        = $atom_structure['sample_description_table'][$i]['data_format'];
                                        $info_quicktime['video']['codec_fourcc_lookup'] = $this->QuicktimeVideoCodecLookup($atom_structure['sample_description_table'][$i]['data_format']);
                                        $info_quicktime['video']['codec']               = $atom_structure['sample_description_table'][$i]['video_encoder_name'];
                                        $info_quicktime['video']['color_depth']         = $atom_structure['sample_description_table'][$i]['video_pixel_color_depth'];
                                        $info_quicktime['video']['color_depth_name']    = $atom_structure['sample_description_table'][$i]['video_pixel_color_name'];

                                        $info['video']['codec']           = $info_quicktime['video']['codec'];
                                        $info['video']['bits_per_sample'] = $info_quicktime['video']['color_depth'];
                                    }
                                    $info['video']['lossless']           = false;
                                    $info['video']['pixel_aspect_ratio'] = (float)1;
                                    break;
                            }
                            break;
                    }
                    switch (strtolower($atom_structure['sample_description_table'][$i]['data_format'])) {
                        case 'mp4a':
                            $info['audio']['dataformat'] = $info_quicktime['audio']['codec'] = 'mp4';
                            break;

                        case '3ivx':
                        case '3iv1':
                        case '3iv2':
                            $info['video']['dataformat'] = '3ivx';
                            break;

                        case 'xvid':
                            $info['video']['dataformat'] = 'xvid';
                            break;

                        case 'mp4v':
                            $info['video']['dataformat'] = 'mpeg4';
                            break;

                        case 'divx':
                        case 'div1':
                        case 'div2':
                        case 'div3':
                        case 'div4':
                        case 'div5':
                        case 'div6':
                            //$TDIVXileInfo['video']['dataformat'] = 'divx';
                            break;

                        default:
                            // do nothing
                            break;
                    }
                    unset($atom_structure['sample_description_table'][$i]['data']);
                }
                break;


            case 'stts': // Sample Table Time-to-Sample atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'        => 1,
                        'flags_raw'      => 3, // hardcoded: 0x0000
                        'number_entries' => 4
                    )
                );
                
                $stts_entries_data_offset = 8;
                $frame_rate_calculator_array = array ();
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                    
                    $atom_structure['time_to_sample_table'][$i]['sample_count']    = getid3_lib::BigEndian2Int(substr($atom_data, $stts_entries_data_offset, 4));
                    $stts_entries_data_offset += 4;
                    
                    $atom_structure['time_to_sample_table'][$i]['sample_duration'] = getid3_lib::BigEndian2Int(substr($atom_data, $stts_entries_data_offset, 4));
                    $stts_entries_data_offset += 4;

                    if (!empty($info_quicktime['time_scale']) && (@$atoms_structure['time_to_sample_table'][$i]['sample_duration'] > 0)) {                        

                        $stts_new_framerate = $info_quicktime['time_scale'] / $atom_structure['time_to_sample_table'][$i]['sample_duration'];
                        if ($stts_new_framerate <= 60) {
                            // some atoms have durations of "1" giving a very large framerate, which probably is not right
                            $info['video']['frame_rate'] = max(@$info['video']['frame_rate'], $stts_new_framerate);
                        }
                    }
                    //@$frame_rate_calculator_array[($info_quicktime['time_scale'] / $atom_structure['time_to_sample_table'][$i]['sample_duration'])] += $atom_structure['time_to_sample_table'][$i]['sample_count'];
                }
                /*
                $stts_frames_total  = 0;
                $stts_seconds_total = 0;
                foreach ($frame_rate_calculator_array as $frames_per_second => $frame_count) {
                    if (($frames_per_second > 60) || ($frames_per_second < 1)) {
                        // not video FPS information, probably audio information
                        $stts_frames_total  = 0;
                        $stts_seconds_total = 0;
                        break;
                    }
                    $stts_frames_total  += $frame_count;
                    $stts_seconds_total += $frame_count / $frames_per_second;
                }
                if (($stts_frames_total > 0) && ($stts_seconds_total > 0)) {
                    if (($stts_frames_total / $stts_seconds_total) > @$info['video']['frame_rate']) {
                        $info['video']['frame_rate'] = $stts_frames_total / $stts_seconds_total;
                    }
                }
                */
                break;


            case 'stss': // Sample Table Sync Sample (key frames) atom
                /*
                $atom_structure['version']        = getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));
                $atom_structure['flags_raw']      = getid3_lib::BigEndian2Int(substr($atom_data,  1, 3)); // hardcoded: 0x0000
                $atom_structure['number_entries'] = getid3_lib::BigEndian2Int(substr($atom_data,  4, 4));
                $stss_entries_data_offset = 8;
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                    $atom_structure['time_to_sample_table'][$i] = getid3_lib::BigEndian2Int(substr($atom_data, $stss_entries_data_offset, 4));
                    $stss_entries_data_offset += 4;
                }
                */
                break;


            case 'stsc': // Sample Table Sample-to-Chunk atom
                /*
                $atom_structure['version']        = getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));
                $atom_structure['flags_raw']      = getid3_lib::BigEndian2Int(substr($atom_data,  1, 3)); // hardcoded: 0x0000
                $atom_structure['number_entries'] = getid3_lib::BigEndian2Int(substr($atom_data,  4, 4));
                $stsc_entries_data_offset = 8;
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                    $atom_structure['sample_to_chunk_table'][$i]['first_chunk']        = getid3_lib::BigEndian2Int(substr($atom_data, $stsc_entries_data_offset, 4));
                    $stsc_entries_data_offset += 4;
                    $atom_structure['sample_to_chunk_table'][$i]['samples_per_chunk']  = getid3_lib::BigEndian2Int(substr($atom_data, $stsc_entries_data_offset, 4));
                    $stsc_entries_data_offset += 4;
                    $atom_structure['sample_to_chunk_table'][$i]['sample_description'] = getid3_lib::BigEndian2Int(substr($atom_data, $stsc_entries_data_offset, 4));
                    $stsc_entries_data_offset += 4;
                }
                */
                break;


            case 'stsz': // Sample Table SiZe atom
                /*
                $atom_structure['version']        = getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));
                $atom_structure['flags_raw']      = getid3_lib::BigEndian2Int(substr($atom_data,  1, 3)); // hardcoded: 0x0000
                $atom_structure['sample_size']    = getid3_lib::BigEndian2Int(substr($atom_data,  4, 4));
                $atom_structure['number_entries'] = getid3_lib::BigEndian2Int(substr($atom_data,  8, 4));
                $stsz_entries_data_offset = 12;
                if ($atom_structure['sample_size'] == 0) {
                    for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                        $atom_structure['sample_size_table'][$i] = getid3_lib::BigEndian2Int(substr($atom_data, $stsz_entries_data_offset, 4));
                        $stsz_entries_data_offset += 4;
                    }
                }
                */
                break;


            case 'stco': // Sample Table Chunk Offset atom
                /*
                $atom_structure['version']        = getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));
                $atom_structure['flags_raw']      = getid3_lib::BigEndian2Int(substr($atom_data,  1, 3)); // hardcoded: 0x0000
                $atom_structure['number_entries'] = getid3_lib::BigEndian2Int(substr($atom_data,  4, 4));
                $stco_entries_data_offset = 8;
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                    $atom_structure['chunk_offset_table'][$i] = getid3_lib::BigEndian2Int(substr($atom_data, $stco_entries_data_offset, 4));
                    $stco_entries_data_offset += 4;
                }
                */
                break;


            case 'dref': // Data REFerence atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'        => 1,
                        'flags_raw'      => 3, // hardcoded: 0x0000
                        'number_entries' => 4
                    )
                );
                
                $dref_data_offset = 8;
                for ($i = 0; $i < $atom_structure['number_entries']; $i++) {
                  
                    getid3_lib::ReadSequence('BigEndian2Int', $atom_structure['data_references'][$i], $atom_data, $dref_data_offset, 
                        array (
                            'size'      => 4,
                            'type'      => -4,
                            'version'   => 1,
                            'flags_raw' => 3  // hardcoded: 0x0000
                        )
                    );
                    $dref_data_offset += 12;
                  
                    $atom_structure['data_references'][$i]['data'] = substr($atom_data, $dref_data_offset, ($atom_structure['data_references'][$i]['size'] - 4 - 4 - 1 - 3));
                    $dref_data_offset += ($atom_structure['data_references'][$i]['size'] - 4 - 4 - 1 - 3);

                    $atom_structure['data_references'][$i]['flags']['self_reference'] = (bool)($atom_structure['data_references'][$i]['flags_raw'] & 0x001);
                }
                break;


            case 'gmin': // base Media INformation atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'       => 1,
                        'flags_raw'     => 3, // hardcoded: 0x0000
                        'graphics_mode' => 2,
                        'opcolor_red'   => 2,
                        'opcolor_green' => 2,
                        'opcolor_blue'  => 2,
                        'balance'       => 2,
                        'reserved'      => 2
                    )
                );
                break;


            case 'smhd': // Sound Media information HeaDer atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'   => 1,
                        'flags_raw' => 3, // hardcoded: 0x0000
                        'balance'   => 2,
                        'reserved'  => 2
                    )
                );
                break;


            case 'vmhd': // Video Media information HeaDer atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'       => 1,
                        'flags_raw'     => 3,
                        'graphics_mode' => 2,
                        'opcolor_red'   => 2,
                        'opcolor_green' => 2,
                        'opcolor_blue'  => 2
                    )
                );
                $atom_structure['flags']['no_lean_ahead'] = (bool)($atom_structure['flags_raw'] & 0x001);
                break;


            case 'hdlr': // HanDLeR reference atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'                => 1,
                        'flags_raw'              => 3, // hardcoded: 0x0000
                        'component_type'         => -4,
                        'component_subtype'      => -4,
                        'component_manufacturer' => -4,
                        'component_flags_raw'    => 4,
                        'component_flags_mask'   => 4
                    )
                );

                $atom_structure['component_name'] = substr(substr($atom_data, 24), 1);       /// Pascal2String

                if (($atom_structure['component_subtype'] == 'STpn') && ($atom_structure['component_manufacturer'] == 'zzzz')) {
                    $info['video']['dataformat'] = 'quicktimevr';
                }
                break;


            case 'mdhd': // MeDia HeaDer atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'       => 1,
                        'flags_raw'     => 3, // hardcoded: 0x0000
                        'creation_time' => 4,
                        'modify_time'   => 4,
                        'time_scale'    => 4,
                        'duration'      => 4,
                        'language_id'   => 2,
                        'quality'       => 2
                    )
                );

                if ($atom_structure['time_scale'] == 0) {
                    throw new getid3_exception('Corrupt Quicktime file: mdhd.time_scale == zero');
                }
                $info_quicktime['time_scale'] = max(@$info['quicktime']['time_scale'], $atom_structure['time_scale']);
                
                $atom_structure['creation_time_unix'] = (int)($atom_structure['creation_time'] - 2082844800); // DateMac2Unix()
                $atom_structure['modify_time_unix']   = (int)($atom_structure['modify_time']   - 2082844800); // DateMac2Unix()
                $atom_structure['playtime_seconds']   = $atom_structure['duration'] / $atom_structure['time_scale'];
                $atom_structure['language']           = $this->QuicktimeLanguageLookup($atom_structure['language_id']);
                if (empty($info['comments']['language']) || (!in_array($atom_structure['language'], $info['comments']['language']))) {
                    $info['comments']['language'][] = $atom_structure['language'];
                }
                break;


            case 'pnot': // Preview atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'modification_date' => 4,   // "standard Macintosh format"
                        'version_number'    => 2,   // hardcoded: 0x00
                        'atom_type'         => -4,  // usually: 'PICT'
                        'atom_index'        => 2    // usually: 0x01
                    )
                );
                $atom_structure['modification_date_unix'] = (int)($atom_structure['modification_date'] - 2082844800); // DateMac2Unix()
                break;


            case 'crgn': // Clipping ReGioN atom
                $atom_structure['region_size']   = getid3_lib::BigEndian2Int(substr($atom_data,  0, 2)); // The Region size, Region boundary box,
                $atom_structure['boundary_box']  = getid3_lib::BigEndian2Int(substr($atom_data,  2, 8)); // and Clipping region data fields
                $atom_structure['clipping_data'] =                           substr($atom_data, 10);           // constitute a QuickDraw region.
                break;


            case 'load': // track LOAD settings atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'preload_start_time' => 4,
                        'preload_duration'   => 4,
                        'preload_flags_raw'  => 4,
                        'default_hints_raw'  => 4
                    )
                );

                $atom_structure['default_hints']['double_buffer'] = (bool)($atom_structure['default_hints_raw'] & 0x0020);
                $atom_structure['default_hints']['high_quality']  = (bool)($atom_structure['default_hints_raw'] & 0x0100);
                break;


            case 'tmcd': // TiMe CoDe atom
            case 'chap': // CHAPter list atom
            case 'sync': // SYNChronization atom
            case 'scpt': // tranSCriPT atom
            case 'ssrc': // non-primary SouRCe atom
                for ($i = 0; $i < (strlen($atom_data) % 4); $i++) {
                    $atom_structure['track_id'][$i] = getid3_lib::BigEndian2Int(substr($atom_data, $i * 4, 4));
                }
                break;


            case 'elst': // Edit LiST atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'        => 1,
                        'flags_raw'      => 3, // hardcoded: 0x0000
                        'number_entries' => 4
                    )
                );    
                        
                for ($i = 0; $i < $atom_structure['number_entries']; $i++ ) {
                    $atom_structure['edit_list'][$i]['track_duration'] = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($i * 12) + 0, 4));
                    $atom_structure['edit_list'][$i]['media_time']     = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($i * 12) + 4, 4));
                    $atom_structure['edit_list'][$i]['media_rate']     = getid3_quicktime::FixedPoint16_16(substr($atom_data, 8 + ($i * 12) + 8, 4));
                }
                break;


            case 'kmat': // compressed MATte atom
                $atom_structure['version']        = getid3_lib::BigEndian2Int(substr($atom_data,  0, 1));
                $atom_structure['flags_raw']      = getid3_lib::BigEndian2Int(substr($atom_data,  1, 3)); // hardcoded: 0x0000
                $atom_structure['matte_data_raw'] =                           substr($atom_data,  4);
                break;


            case 'ctab': // Color TABle atom
                $atom_structure['color_table_seed']   = getid3_lib::BigEndian2Int(substr($atom_data,  0, 4)); // hardcoded: 0x00000000
                $atom_structure['color_table_flags']  = getid3_lib::BigEndian2Int(substr($atom_data,  4, 2)); // hardcoded: 0x8000
                $atom_structure['color_table_size']   = getid3_lib::BigEndian2Int(substr($atom_data,  6, 2)) + 1;
                for ($colortableentry = 0; $colortableentry < $atom_structure['color_table_size']; $colortableentry++) {
                    $atom_structure['color_table'][$colortableentry]['alpha'] = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($colortableentry * 8) + 0, 2));
                    $atom_structure['color_table'][$colortableentry]['red']   = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($colortableentry * 8) + 2, 2));
                    $atom_structure['color_table'][$colortableentry]['green'] = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($colortableentry * 8) + 4, 2));
                    $atom_structure['color_table'][$colortableentry]['blue']  = getid3_lib::BigEndian2Int(substr($atom_data, 8 + ($colortableentry * 8) + 6, 2));
                }
                break;


            case 'mvhd': // MoVie HeaDer atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'       => 1,
                        'flags_raw'     => 3,
                        'creation_time' => 4,
                        'modify_time'   => 4,
                        'time_scale'    => 4,
                        'duration'      => 4
                    )
                );

                $atom_structure['preferred_rate']     = getid3_quicktime::FixedPoint16_16(substr($atom_data, 20, 4));
                $atom_structure['preferred_volume']   =   getid3_quicktime::FixedPoint8_8(substr($atom_data, 24, 2));
                $atom_structure['reserved']           =                                   substr($atom_data, 26, 10);
                $atom_structure['matrix_a']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 36, 4));
                $atom_structure['matrix_b']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 40, 4));
                $atom_structure['matrix_u']           =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 44, 4));
                $atom_structure['matrix_c']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 48, 4));
                $atom_structure['matrix_d']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 52, 4));
                $atom_structure['matrix_v']           =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 56, 4));
                $atom_structure['matrix_x']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 60, 4));
                $atom_structure['matrix_y']           = getid3_quicktime::FixedPoint16_16(substr($atom_data, 64, 4));
                $atom_structure['matrix_w']           =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 68, 4));
                
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 72, 
                    array (
                        'preview_time'       => 4,
                        'preview_duration'   => 4,
                        'poster_time'        => 4,
                        'selection_time'     => 4,
                        'selection_duration' => 4,
                        'current_time'       => 4,
                        'next_track_id'      => 4
                    )
                );

                if ($atom_structure['time_scale'] == 0) {
                    throw new getid3_exception('Corrupt Quicktime file: mvhd.time_scale == zero');
                }
                
                $atom_structure['creation_time_unix']        = (int)($atom_structure['creation_time'] - 2082844800); // DateMac2Unix()
                $atom_structure['modify_time_unix']          = (int)($atom_structure['modify_time']   - 2082844800); // DateMac2Unix()
                $info_quicktime['time_scale'] = max(@$info['quicktime']['time_scale'], $atom_structure['time_scale']);
                $info_quicktime['display_scale'] = $atom_structure['matrix_a'];
                $info['playtime_seconds']           = $atom_structure['duration'] / $atom_structure['time_scale'];
                break;


            case 'tkhd': // TracK HeaDer atom
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'version'         => 1,
                        'flags_raw'       => 3,
                        'creation_time'   => 4,
                        'modify_time'     => 4,
                        'trackid'         => 4,
                        'reserved1'       => 4,
                        'duration'        => 4,
                        'reserved2'       => 8,
                        'layer'           => 2,
                        'alternate_group' => 2
                    )
                );
                
                $atom_structure['volume']              =   getid3_quicktime::FixedPoint8_8(substr($atom_data, 36, 2));
                $atom_structure['reserved3']           =         getid3_lib::BigEndian2Int(substr($atom_data, 38, 2));
                $atom_structure['matrix_a']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 40, 4));
                $atom_structure['matrix_b']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 44, 4));
                $atom_structure['matrix_u']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 48, 4));
                $atom_structure['matrix_c']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 52, 4));
                $atom_structure['matrix_v']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 56, 4));
                $atom_structure['matrix_d']            = getid3_quicktime::FixedPoint16_16(substr($atom_data, 60, 4));
                $atom_structure['matrix_x']            =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 64, 4));
                $atom_structure['matrix_y']            =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 68, 4));
                $atom_structure['matrix_w']            =  getid3_quicktime::FixedPoint2_30(substr($atom_data, 72, 4));
                $atom_structure['width']               = getid3_quicktime::FixedPoint16_16(substr($atom_data, 76, 4));
                $atom_structure['height']              = getid3_quicktime::FixedPoint16_16(substr($atom_data, 80, 4));

                $atom_structure['flags']['enabled']    = (bool)($atom_structure['flags_raw'] & 0x0001);
                $atom_structure['flags']['in_movie']   = (bool)($atom_structure['flags_raw'] & 0x0002);
                $atom_structure['flags']['in_preview'] = (bool)($atom_structure['flags_raw'] & 0x0004);
                $atom_structure['flags']['in_poster']  = (bool)($atom_structure['flags_raw'] & 0x0008);
                $atom_structure['creation_time_unix']  = (int)($atom_structure['creation_time'] - 2082844800); // DateMac2Unix()
                $atom_structure['modify_time_unix']    = (int)($atom_structure['modify_time']   - 2082844800); // DateMac2Unix()

                if (!isset($info['video']['resolution_x']) || !isset($info['video']['resolution_y'])) {
                    $info['video']['resolution_x'] = $atom_structure['width'];
                    $info['video']['resolution_y'] = $atom_structure['height'];
                }
                
                if ($atom_structure['flags']['enabled'] == 1) {
                    $info['video']['resolution_x'] = max($info['video']['resolution_x'], $atom_structure['width']);
                    $info['video']['resolution_y'] = max($info['video']['resolution_y'], $atom_structure['height']);
                }
                
                if (!empty($info['video']['resolution_x']) && !empty($info['video']['resolution_y'])) {
                    $info_quicktime['video']['resolution_x'] = $info['video']['resolution_x'];
                    $info_quicktime['video']['resolution_y'] = $info['video']['resolution_y'];
                } else {
                    unset($info['video']['resolution_x']);
                    unset($info['video']['resolution_y']);
                    unset($info_quicktime['video']);
                }
                break;


            case 'meta': // METAdata atom
                // http://www.geocities.com/xhelmboyx/quicktime/formats/qti-layout.txt
                $next_tag_position = strpos($atom_data, '©');
                while ($next_tag_position < strlen($atom_data)) {
                    $meta_item_size = getid3_lib::BigEndian2Int(substr($atom_data, $next_tag_position - 4, 4)) - 4;
                    if ($meta_item_size == -4) {
                        break;
                    }
                    $meta_item_raw  = substr($atom_data, $next_tag_position, $meta_item_size);
                    $meta_item_key  = substr($meta_item_raw, 0, 4);
                    $meta_item_data = substr($meta_item_raw, 20);
                    $next_tag_position += $meta_item_size + 4;

                    $this->CopyToAppropriateCommentsSection($meta_item_key, $meta_item_data);
                }
                break;

            case 'ftyp': // FileTYPe (?) atom (for MP4 it seems)
                getid3_lib::ReadSequence('BigEndian2Int', $atom_structure, $atom_data, 0, 
                    array (
                        'signature' => -4,
                        'unknown_1' => 4,
                        'fourcc'    => -4,
                    )
                );
                break;

            case 'mdat': // Media DATa atom
            case 'free': // FREE space atom
            case 'skip': // SKIP atom
            case 'wide': // 64-bit expansion placeholder atom
                // 'mdat' data is too big to deal with, contains no useful metadata
                // 'free', 'skip' and 'wide' are just padding, contains no useful data at all

                // When writing QuickTime files, it is sometimes necessary to update an atom's size.
                // It is impossible to update a 32-bit atom to a 64-bit atom since the 32-bit atom
                // is only 8 bytes in size, and the 64-bit atom requires 16 bytes. Therefore, QuickTime
                // puts an 8-byte placeholder atom before any atoms it may have to update the size of.
                // In this way, if the atom needs to be converted from a 32-bit to a 64-bit atom, the
                // placeholder atom can be overwritten to obtain the necessary 8 extra bytes.
                // The placeholder atom has a type of kWideAtomPlaceholderType ( 'wide' ).
                break;


            case 'nsav': // NoSAVe atom
                // http://developer.apple.com/technotes/tn/tn2038.html
                $atom_structure['data'] = getid3_lib::BigEndian2Int(substr($atom_data,  0, 4));
                break;

            case 'ctyp': // Controller TYPe atom (seen on QTVR)
                // http://homepages.slingshot.co.nz/~helmboy/quicktime/formats/qtm-layout.txt
                // some controller names are:
                //   0x00 + 'std' for linear movie
                //   'none' for no controls
                $atom_structure['ctyp'] = substr($atom_data, 0, 4);
                switch ($atom_structure['ctyp']) {
                    case 'qtvr':
                        $info['video']['dataformat'] = 'quicktimevr';
                        break;
                }
                break;

            case 'pano': // PANOrama track (seen on QTVR)
                $atom_structure['pano'] = getid3_lib::BigEndian2Int(substr($atom_data,  0, 4));
                break;
                
             case 'hint': // HINT track
             case 'hinf': //
             case 'hinv': //
             case 'hnti': //
                     $info['quicktime']['hinting'] = true;
                     break;

            case 'imgt': // IMaGe Track reference (kQTVRImageTrackRefType) (seen on QTVR)
                for ($i = 0; $i < ($atom_structure['size'] - 8); $i += 4) {
                    $atom_structure['imgt'][] = getid3_lib::BigEndian2Int(substr($atom_data, $i, 4));
                }
                break;

            case 'FXTC': // Something to do with Adobe After Effects (?)
            case 'PrmA':
            case 'code':
            case 'FIEL': // this is NOT "fiel" (Field Ordering) as describe here: http://developer.apple.com/documentation/QuickTime/QTFF/QTFFChap3/chapter_4_section_2.html
                // Observed-but-not-handled atom types are just listed here
                // to prevent warnings being generated
                $atom_structure['data'] = $atom_data;
                break;

            default:
                $getid3->warning('Unknown QuickTime atom type: "'.$atom_name.'" at offset '.$base_offset);
                $atom_structure['data'] = $atom_data;
                break;
        }
        array_pop($atom_hierarchy);
        return $atom_structure;
    }



    private function QuicktimeParseContainerAtom($atom_data, $base_offset, &$atom_hierarchy) {
        
        if ((strlen($atom_data) == 4) && (getid3_lib::BigEndian2Int($atom_data) == 0x00000000)) {
            return false;
        }
        
        $atom_structure = false;
        $subatom_offset = 0;
        
        while ($subatom_offset < strlen($atom_data)) {
         
            $subatom_size = getid3_lib::BigEndian2Int(substr($atom_data, $subatom_offset + 0, 4));
            $subatom_name =                           substr($atom_data, $subatom_offset + 4, 4);
            $subatom_data =                           substr($atom_data, $subatom_offset + 8, $subatom_size - 8);
            
            if ($subatom_size == 0) {
                // Furthermore, for historical reasons the list of atoms is optionally
                // terminated by a 32-bit integer set to 0. If you are writing a program
                // to read user data atoms, you should allow for the terminating 0.
                return $atom_structure;
            }

            $atom_structure[] = $this->QuicktimeParseAtom($subatom_name, $subatom_size, $subatom_data, $base_offset + $subatom_offset, $atom_hierarchy);

            $subatom_offset += $subatom_size;
        }
        return $atom_structure;
    }
    
    
    
    private function CopyToAppropriateCommentsSection($key_name, $data) {

        // http://www.geocities.com/xhelmboyx/quicktime/formats/qtm-layout.txt

        static $translator = array (
            '©cpy' => 'copyright',
            '©day' => 'creation_date',
            '©dir' => 'director',
            '©ed1' => 'edit1',
            '©ed2' => 'edit2',
            '©ed3' => 'edit3',
            '©ed4' => 'edit4',
            '©ed5' => 'edit5',
            '©ed6' => 'edit6',
            '©ed7' => 'edit7',
            '©ed8' => 'edit8',
            '©ed9' => 'edit9',
            '©fmt' => 'format',
            '©inf' => 'information',
            '©prd' => 'producer',
            '©prf' => 'performers',
            '©req' => 'system_requirements',
            '©src' => 'source_credit',
            '©wrt' => 'writer',
            '©nam' => 'title',
            '©cmt' => 'comment',
            '©wrn' => 'warning',
            '©hst' => 'host_computer',
            '©mak' => 'make',
            '©mod' => 'model',
            '©PRD' => 'product',
            '©swr' => 'software',
            '©aut' => 'author',
            '©ART' => 'artist',
            '©trk' => 'track',
            '©alb' => 'album',
            '©com' => 'comment',
            '©gen' => 'genre',
            '©ope' => 'composer',
            '©url' => 'url',
            '©enc' => 'encoder'
        );

        if (isset($translator[$key_name])) {
            $this->getid3->info['quicktime']['comments'][$translator[$key_name]][] = $data;
        }

        return true;
    }



    public static function QuicktimeLanguageLookup($language_id) {

        static $lookup = array (
            0   => 'English',
            1   => 'French',
            2   => 'German',
            3   => 'Italian',
            4   => 'Dutch',
            5   => 'Swedish',
            6   => 'Spanish',
            7   => 'Danish',
            8   => 'Portuguese',
            9   => 'Norwegian',
            10  => 'Hebrew',
            11  => 'Japanese',
            12  => 'Arabic',
            13  => 'Finnish',
            14  => 'Greek',
            15  => 'Icelandic',
            16  => 'Maltese',
            17  => 'Turkish',
            18  => 'Croatian',
            19  => 'Chinese (Traditional)',
            20  => 'Urdu',
            21  => 'Hindi',
            22  => 'Thai',
            23  => 'Korean',
            24  => 'Lithuanian',
            25  => 'Polish',
            26  => 'Hungarian',
            27  => 'Estonian',
            28  => 'Lettish',
            28  => 'Latvian',
            29  => 'Saamisk',
            29  => 'Lappish',
            30  => 'Faeroese',
            31  => 'Farsi',
            31  => 'Persian',
            32  => 'Russian',
            33  => 'Chinese (Simplified)',
            34  => 'Flemish',
            35  => 'Irish',
            36  => 'Albanian',
            37  => 'Romanian',
            38  => 'Czech',
            39  => 'Slovak',
            40  => 'Slovenian',
            41  => 'Yiddish',
            42  => 'Serbian',
            43  => 'Macedonian',
            44  => 'Bulgarian',
            45  => 'Ukrainian',
            46  => 'Byelorussian',
            47  => 'Uzbek',
            48  => 'Kazakh',
            49  => 'Azerbaijani',
            50  => 'AzerbaijanAr',
            51  => 'Armenian',
            52  => 'Georgian',
            53  => 'Moldavian',
            54  => 'Kirghiz',
            55  => 'Tajiki',
            56  => 'Turkmen',
            57  => 'Mongolian',
            58  => 'MongolianCyr',
            59  => 'Pashto',
            60  => 'Kurdish',
            61  => 'Kashmiri',
            62  => 'Sindhi',
            63  => 'Tibetan',
            64  => 'Nepali',
            65  => 'Sanskrit',
            66  => 'Marathi',
            67  => 'Bengali',
            68  => 'Assamese',
            69  => 'Gujarati',
            70  => 'Punjabi',
            71  => 'Oriya',
            72  => 'Malayalam',
            73  => 'Kannada',
            74  => 'Tamil',
            75  => 'Telugu',
            76  => 'Sinhalese',
            77  => 'Burmese',
            78  => 'Khmer',
            79  => 'Lao',
            80  => 'Vietnamese',
            81  => 'Indonesian',
            82  => 'Tagalog',
            83  => 'MalayRoman',
            84  => 'MalayArabic',
            85  => 'Amharic',
            86  => 'Tigrinya',
            87  => 'Galla',
            87  => 'Oromo',
            88  => 'Somali',
            89  => 'Swahili',
            90  => 'Ruanda',
            91  => 'Rundi',
            92  => 'Chewa',
            93  => 'Malagasy',
            94  => 'Esperanto',
            128 => 'Welsh',
            129 => 'Basque',
            130 => 'Catalan',
            131 => 'Latin',
            132 => 'Quechua',
            133 => 'Guarani',
            134 => 'Aymara',
            135 => 'Tatar',
            136 => 'Uighur',
            137 => 'Dzongkha',
            138 => 'JavaneseRom'
        );
        
        return (isset($lookup[$language_id]) ? $lookup[$language_id] : 'invalid');
    }



    public static function QuicktimeVideoCodecLookup($codec_id) {

        static $lookup = array (
            '3IVX' => '3ivx MPEG-4',
            '3IV1' => '3ivx MPEG-4 v1',
            '3IV2' => '3ivx MPEG-4 v2',
            'avr ' => 'AVR-JPEG',
            'base' => 'Base',
            'WRLE' => 'BMP',
            'cvid' => 'Cinepak',
            'clou' => 'Cloud',
            'cmyk' => 'CMYK',
            'yuv2' => 'ComponentVideo',
            'yuvu' => 'ComponentVideoSigned',
            'yuvs' => 'ComponentVideoUnsigned',
            'dvc ' => 'DVC-NTSC',
            'dvcp' => 'DVC-PAL',
            'dvpn' => 'DVCPro-NTSC',
            'dvpp' => 'DVCPro-PAL',
            'fire' => 'Fire',
            'flic' => 'FLC',
            'b48r' => '48RGB',
            'gif ' => 'GIF',
            'smc ' => 'Graphics',
            'h261' => 'H261',
            'h263' => 'H263',
            'IV41' => 'Indeo4',
            'jpeg' => 'JPEG',
            'PNTG' => 'MacPaint',
            'msvc' => 'Microsoft Video1',
            'mjpa' => 'Motion JPEG-A',
            'mjpb' => 'Motion JPEG-B',
            'myuv' => 'MPEG YUV420',
            'dmb1' => 'OpenDML JPEG',
            'kpcd' => 'PhotoCD',
            '8BPS' => 'Planar RGB',
            'png ' => 'PNG',
            'qdrw' => 'QuickDraw',
            'qdgx' => 'QuickDrawGX',
            'raw ' => 'RAW',
            '.SGI' => 'SGI',
            'b16g' => '16Gray',
            'b64a' => '64ARGB',
            'SVQ1' => 'Sorenson Video 1',
            'SVQ1' => 'Sorenson Video 3',
            'syv9' => 'Sorenson YUV9',
            'tga ' => 'Targa',
            'b32a' => '32AlphaGray',
            'tiff' => 'TIFF',
            'path' => 'Vector',
            'rpza' => 'Video',
            'ripl' => 'WaterRipple',
            'WRAW' => 'Windows RAW',
            'y420' => 'YUV420'
        );
        
        return (isset($lookup[$codec_id]) ? $lookup[$codec_id] : '');
    }



    public static function QuicktimeAudioCodecLookup($codec_id) {

        static $lookup = array (
            '.mp3'          => 'Fraunhofer MPEG Layer-III alias',
            'aac '          => 'ISO/IEC 14496-3 AAC',
            'agsm'          => 'Apple GSM 10:1',
            'alac'          => 'Apple Lossless Audio Codec',
            'alaw'          => 'A-law 2:1',
            'conv'          => 'Sample Format',
            'dvca'          => 'DV',
            'dvi '          => 'DV 4:1',
            'eqal'          => 'Frequency Equalizer',
            'fl32'          => '32-bit Floating Point',
            'fl64'          => '64-bit Floating Point',
            'ima4'          => 'Interactive Multimedia Association 4:1',
            'in24'          => '24-bit Integer',
            'in32'          => '32-bit Integer',
            'lpc '          => 'LPC 23:1',
            'MAC3'          => 'Macintosh Audio Compression/Expansion (MACE) 3:1',
            'MAC6'          => 'Macintosh Audio Compression/Expansion (MACE) 6:1',
            'mixb'          => '8-bit Mixer',
            'mixw'          => '16-bit Mixer',
            'mp4a'          => 'ISO/IEC 14496-3 AAC',
            "MS'\x00\x02"   => 'Microsoft ADPCM',
            "MS'\x00\x11"   => 'DV IMA',
            "MS\x00\x55"    => 'Fraunhofer MPEG Layer III',
            'NONE'          => 'No Encoding',
            'Qclp'          => 'Qualcomm PureVoice',
            'QDM2'          => 'QDesign Music 2',
            'QDMC'          => 'QDesign Music 1',
            'ratb'          => '8-bit Rate',
            'ratw'          => '16-bit Rate',
            'raw '          => 'raw PCM',
            'sour'          => 'Sound Source',
            'sowt'          => 'signed/two\'s complement (Little Endian)',
            'str1'          => 'Iomega MPEG layer II',
            'str2'          => 'Iomega MPEG *layer II',
            'str3'          => 'Iomega MPEG **layer II',
            'str4'          => 'Iomega MPEG ***layer II',
            'twos'          => 'signed/two\'s complement (Big Endian)',
            'ulaw'          => 'mu-law 2:1',
        );
        
        return (isset($lookup[$codec_id]) ? $lookup[$codec_id] : '');
    }



    public static function QuicktimeDCOMLookup($compression_id) {

        static $lookup = array (
            'zlib' => 'ZLib Deflate',
            'adec' => 'Apple Compression'
        );

        return (isset($lookup[$compression_id]) ? $lookup[$compression_id] : '');
    }



    public static function QuicktimeColorNameLookup($color_depth_id) {
  
        static $lookup = array (
            1  => '2-color (monochrome)',
            2  => '4-color',
            4  => '16-color',
            8  => '256-color',
            16 => 'thousands (16-bit color)',
            24 => 'millions (24-bit color)',
            32 => 'millions+ (32-bit color)',
            33 => 'black & white',
            34 => '4-gray',
            36 => '16-gray',
            40 => '256-gray',
        );
        
        return (isset($lookup[$color_depth_id]) ? $lookup[$color_depth_id] : 'invalid');
    }



    public static function NoNullString($null_terminated_string) {

        // remove the single null terminator on null terminated strings
        if (substr($null_terminated_string, strlen($null_terminated_string) - 1, 1) === "\x00") {
            return substr($null_terminated_string, 0, strlen($null_terminated_string) - 1);
        }
        
        return $null_terminated_string;
    }
    
    
    
    public static function FixedPoint8_8($raw_data) {

        return getid3_lib::BigEndian2Int($raw_data{0}) + (float)(getid3_lib::BigEndian2Int($raw_data{1}) / 256);
    }



    public static function FixedPoint16_16($raw_data) {
        
        return getid3_lib::BigEndian2Int(substr($raw_data, 0, 2)) + (float)(getid3_lib::BigEndian2Int(substr($raw_data, 2, 2)) / 65536);
    }



    public static function FixedPoint2_30($raw_data) {
        
        $binary_string = getid3_lib::BigEndian2Bin($raw_data);
        return bindec(substr($binary_string, 0, 2)) + (float)(bindec(substr($binary_string, 2, 30)) / 1073741824);
    }

}

?>