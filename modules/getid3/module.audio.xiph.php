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
// | module.audio.xiph.php                                                |
// | Module for analyzing Xiph.org audio file formats:                    |
// | Ogg Vorbis, FLAC, OggFLAC and Speex - not Ogg Theora                 |
// | dependencies: module.lib.image_size.php (optional)                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.xiph.php,v 1.5 2006/12/03 21:12:43 ah Exp $

        
        
class getid3_xiph extends getid3_handler
{
    
    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        if ($getid3->option_tags_images) {        
            $getid3->include_module('lib.image_size');
        }
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        
        $magic = fread($getid3->fp, 4);
        
        if ($magic == 'OggS') {
            return $this->ParseOgg();
        }
        
        if ($magic == 'fLaC') {
            return $this->ParseFLAC();
        }
        
    }
    
    
    
    private function ParseOgg() {
        
        $getid3 = $this->getid3;
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        
        $getid3->info['audio'] = $getid3->info['ogg'] = array ();
        $info_ogg   = &$getid3->info['ogg'];				
        $info_audio = &$getid3->info['audio'];
        
        $getid3->info['fileformat'] = 'ogg';


        //// Page 1 - Stream Header

        $ogg_page_info = $this->ParseOggPageHeader();
        $info_ogg['pageheader'][$ogg_page_info['page_seqno']] = $ogg_page_info;

        if (ftell($getid3->fp) >= getid3::FREAD_BUFFER_SIZE) {
            throw new getid3_exception('Could not find start of Ogg page in the first '.getid3::FREAD_BUFFER_SIZE.' bytes (this might not be an Ogg file?)');
        }

        $file_data = fread($getid3->fp, $ogg_page_info['page_length']);
        $file_data_offset = 0;


        // OggFLAC
        if (substr($file_data, 0, 4) == 'fLaC') {

            $info_audio['dataformat']   = 'flac';
            $info_audio['bitrate_mode'] = 'vbr';
            $info_audio['lossless']     = true;

        } 
    
    
        // Ogg Vorbis
        elseif (substr($file_data, 1, 6) == 'vorbis') {

            $info_audio['dataformat'] = 'vorbis';
            $info_audio['lossless']   = false;

            $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int($file_data[0]);
            $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['stream_type'] = substr($file_data, 1, 6); // hard-coded to 'vorbis'
            
            getid3_lib::ReadSequence('LittleEndian2Int', $info_ogg, $file_data, 7, 
                array (
                    'bitstreamversion' => 4,
                    'numberofchannels' => 1,
                    'samplerate'       => 4,
                    'bitrate_max'      => 4,
                    'bitrate_nominal'  => 4,
                    'bitrate_min'      => 4
                )
            );
                                                                                                                     
            $n28 = getid3_lib::LittleEndian2Int($file_data{28});
            $info_ogg['blocksize_small']  = pow(2, $n28 & 0x0F);
            $info_ogg['blocksize_large']  = pow(2, ($n28 & 0xF0) >> 4);
            $info_ogg['stop_bit']         = $n28;
            
            $info_audio['channels']       = $info_ogg['numberofchannels'];
            $info_audio['sample_rate']    = $info_ogg['samplerate'];

            $info_audio['bitrate_mode'] = 'vbr';     // overridden if actually abr

            if ($info_ogg['bitrate_max'] == 0xFFFFFFFF) {
                unset($info_ogg['bitrate_max']);
                $info_audio['bitrate_mode'] = 'abr';
            }
            
            if ($info_ogg['bitrate_nominal'] == 0xFFFFFFFF) {
                unset($info_ogg['bitrate_nominal']);
            }
            
            if ($info_ogg['bitrate_min'] == 0xFFFFFFFF) {
                unset($info_ogg['bitrate_min']);
                $info_audio['bitrate_mode'] = 'abr';
            }
        }
    

        // Speex
        elseif (substr($file_data, 0, 8) == 'Speex   ') {

            // http://www.speex.org/manual/node10.html

            $info_audio['dataformat']   = 'speex';
            $getid3->info['mime_type']  = 'audio/speex';
            $info_audio['bitrate_mode'] = 'abr';
            $info_audio['lossless']     = false;

            getid3_lib::ReadSequence('LittleEndian2Int', $info_ogg['pageheader'][$ogg_page_info['page_seqno']], $file_data, 0, 
                array (
                    'speex_string'           => -8, 		// hard-coded to 'Speex   '
                    'speex_version'          => -20,      	// string                  
                    'speex_version_id'       => 4,
                    'header_size'            => 4,
                    'rate'                   => 4,
                    'mode'                   => 4,
                    'mode_bitstream_version' => 4,
                    'nb_channels'            => 4,
                    'bitrate'                => 4,
                    'framesize'              => 4,
                    'vbr'                    => 4,
                    'frames_per_packet'      => 4,
                    'extra_headers'          => 4,
                    'reserved1'              => 4,
                    'reserved2'              => 4
                )
            );
                
            $getid3->info['speex']['speex_version'] = trim($info_ogg['pageheader'][$ogg_page_info['page_seqno']]['speex_version']);
            $getid3->info['speex']['sample_rate']   = $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['rate'];
            $getid3->info['speex']['channels']      = $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['nb_channels'];
            $getid3->info['speex']['vbr']           = (bool)$info_ogg['pageheader'][$ogg_page_info['page_seqno']]['vbr'];
            $getid3->info['speex']['band_type']     = getid3_xiph::SpeexBandModeLookup($info_ogg['pageheader'][$ogg_page_info['page_seqno']]['mode']);

            $info_audio['sample_rate'] = $getid3->info['speex']['sample_rate'];
            $info_audio['channels']    = $getid3->info['speex']['channels'];
            
            if ($getid3->info['speex']['vbr']) {
                $info_audio['bitrate_mode'] = 'vbr';
            }
        }

        // Unsupported Ogg file
        else {

            throw new getid3_exception('Expecting either "Speex   " or "vorbis" identifier strings, found neither');
        }


        //// Page 2 - Comment Header

        $ogg_page_info = $this->ParseOggPageHeader();
        $info_ogg['pageheader'][$ogg_page_info['page_seqno']] = $ogg_page_info;

        switch ($info_audio['dataformat']) {

            case 'vorbis':
                $file_data = fread($getid3->fp, $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['page_length']);
                $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['packet_type'] = getid3_lib::LittleEndian2Int(substr($file_data, 0, 1));
                $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['stream_type'] = substr($file_data, 1, 6); // hard-coded to 'vorbis'
                $this->ParseVorbisCommentsFilepointer();
                break;

            case 'flac':
                if (!$this->FLACparseMETAdata()) {
                    throw new getid3_exception('Failed to parse FLAC headers');
                }
                break;

            case 'speex':
                fseek($getid3->fp, $info_ogg['pageheader'][$ogg_page_info['page_seqno']]['page_length'], SEEK_CUR);
                $this->ParseVorbisCommentsFilepointer();
                break;
        }


        //// Last Page - Number of Samples

        fseek($getid3->fp, max($getid3->info['avdataend'] - getid3::FREAD_BUFFER_SIZE, 0), SEEK_SET);
        $last_chunk_of_ogg = strrev(fread($getid3->fp, getid3::FREAD_BUFFER_SIZE));
        
        if ($last_OggS_postion = strpos($last_chunk_of_ogg, 'SggO')) {
            fseek($getid3->fp, $getid3->info['avdataend'] - ($last_OggS_postion + strlen('SggO')), SEEK_SET);
            $getid3->info['avdataend'] = ftell($getid3->fp);
            $info_ogg['pageheader']['eos'] = $this->ParseOggPageHeader();
            $info_ogg['samples']           = $info_ogg['pageheader']['eos']['pcm_abs_position'];
            $info_ogg['bitrate_average']   = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / ($info_ogg['samples'] / $info_audio['sample_rate']);
        }

        if (!empty($info_ogg['bitrate_average'])) {
            $info_audio['bitrate'] = $info_ogg['bitrate_average'];
        } elseif (!empty($info_ogg['bitrate_nominal'])) {
            $info_audio['bitrate'] = $info_ogg['bitrate_nominal'];
        } elseif (!empty($info_ogg['bitrate_min']) && !empty($info_ogg['bitrate_max'])) {
            $info_audio['bitrate'] = ($info_ogg['bitrate_min'] + $info_ogg['bitrate_max']) / 2;
        }
        if (isset($info_audio['bitrate']) && !isset($getid3->info['playtime_seconds'])) {
            $getid3->info['playtime_seconds'] = (float)((($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $info_audio['bitrate']);
        }

        if (isset($info_ogg['vendor'])) {
            $info_audio['encoder'] = preg_replace('/^Encoded with /', '', $info_ogg['vendor']);

            // Vorbis only
            if ($info_audio['dataformat'] == 'vorbis') {

                // Vorbis 1.0 starts with Xiph.Org
                if  (preg_match('/^Xiph.Org/', $info_audio['encoder'])) {

                    if ($info_audio['bitrate_mode'] == 'abr') {

                        // Set -b 128 on abr files
                        $info_audio['encoder_options'] = '-b '.round($info_ogg['bitrate_nominal'] / 1000);

                    } elseif (($info_audio['bitrate_mode'] == 'vbr') && ($info_audio['channels'] == 2) && ($info_audio['sample_rate'] >= 44100) && ($info_audio['sample_rate'] <= 48000)) {
                        // Set -q N on vbr files
                        $info_audio['encoder_options'] = '-q '.getid3_xiph::GetQualityFromNominalBitrate($info_ogg['bitrate_nominal']);
                    }
                }

                if (empty($info_audio['encoder_options']) && !empty($info_ogg['bitrate_nominal'])) {
                    $info_audio['encoder_options'] = 'Nominal bitrate: '.intval(round($info_ogg['bitrate_nominal'] / 1000)).'kbps';
                }
            }
        }

        return true;
    }



    private function ParseOggPageHeader() {
        
        $getid3 = $this->getid3;
        
        // http://xiph.org/ogg/vorbis/doc/framing.html
        $ogg_header['page_start_offset'] = ftell($getid3->fp);      // where we started from in the file
        
        $file_data = fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);
        $file_data_offset = 0;
        
        while ((substr($file_data, $file_data_offset++, 4) != 'OggS')) {
            if ((ftell($getid3->fp) - $ogg_header['page_start_offset']) >= getid3::FREAD_BUFFER_SIZE) {
                // should be found before here
                return false;
            }
            if ((($file_data_offset + 28) > strlen($file_data)) || (strlen($file_data) < 28)) {
                if (feof($getid3->fp) || (($file_data .= fread($getid3->fp, getid3::FREAD_BUFFER_SIZE)) === false)) {
                    // get some more data, unless eof, in which case fail
                    return false;
                }
            }
        }
        
        $file_data_offset += 3; // page, delimited by 'OggS'
        
        getid3_lib::ReadSequence('LittleEndian2Int', $ogg_header, $file_data, $file_data_offset, 
            array (
                'stream_structver' => 1,
                'flags_raw'        => 1,
                'pcm_abs_position' => 8,
                'stream_serialno'  => 4,
                'page_seqno'       => 4,
                'page_checksum'    => 4,
                'page_segments'    => 1
            )
        );
        
        $file_data_offset += 23;

        $ogg_header['flags']['fresh'] = (bool)($ogg_header['flags_raw'] & 0x01); // fresh packet
        $ogg_header['flags']['bos']   = (bool)($ogg_header['flags_raw'] & 0x02); // first page of logical bitstream (bos)
        $ogg_header['flags']['eos']   = (bool)($ogg_header['flags_raw'] & 0x04); // last page of logical bitstream (eos)

        $ogg_header['page_length'] = 0;
        for ($i = 0; $i < $ogg_header['page_segments']; $i++) {
            $ogg_header['segment_table'][$i] = getid3_lib::LittleEndian2Int($file_data{$file_data_offset++});
            $ogg_header['page_length'] += $ogg_header['segment_table'][$i];
        }
        $ogg_header['header_end_offset'] = $ogg_header['page_start_offset'] + $file_data_offset;
        $ogg_header['page_end_offset']   = $ogg_header['header_end_offset'] + $ogg_header['page_length'];
        fseek($getid3->fp, $ogg_header['header_end_offset'], SEEK_SET);

        return $ogg_header;
    }


    
    private function ParseVorbisCommentsFilepointer() {
        
        $getid3 = $this->getid3;

        $original_offset      = ftell($getid3->fp);
        $comment_start_offset = $original_offset;
        $comment_data_offset  = 0;
        $vorbis_comment_page  = 1;

        switch ($getid3->info['audio']['dataformat']) {
            
            case 'vorbis':
                $comment_start_offset = $getid3->info['ogg']['pageheader'][$vorbis_comment_page]['page_start_offset'];  // Second Ogg page, after header block
                fseek($getid3->fp, $comment_start_offset, SEEK_SET);
                $comment_data_offset = 27 + $getid3->info['ogg']['pageheader'][$vorbis_comment_page]['page_segments'];
                $comment_data = fread($getid3->fp, getid3_xiph::OggPageSegmentLength($getid3->info['ogg']['pageheader'][$vorbis_comment_page], 1) + $comment_data_offset);
                $comment_data_offset += (strlen('vorbis') + 1);
                break;
                

            case 'flac':
                fseek($getid3->fp, $getid3->info['flac']['VORBIS_COMMENT']['raw']['offset'] + 4, SEEK_SET);
                $comment_data = fread($getid3->fp, $getid3->info['flac']['VORBIS_COMMENT']['raw']['block_length']);
                break;
                

            case 'speex':
                $comment_start_offset = $getid3->info['ogg']['pageheader'][$vorbis_comment_page]['page_start_offset'];  // Second Ogg page, after header block
                fseek($getid3->fp, $comment_start_offset, SEEK_SET);
                $comment_data_offset = 27 + $getid3->info['ogg']['pageheader'][$vorbis_comment_page]['page_segments'];
                $comment_data = fread($getid3->fp, getid3_xiph::OggPageSegmentLength($getid3->info['ogg']['pageheader'][$vorbis_comment_page], 1) + $comment_data_offset);
                break;
                

            default:
                return false;
        }

        $vendor_size = getid3_lib::LittleEndian2Int(substr($comment_data, $comment_data_offset, 4));
        $comment_data_offset += 4;

        $getid3->info['ogg']['vendor'] = substr($comment_data, $comment_data_offset, $vendor_size);
        $comment_data_offset += $vendor_size;

        $comments_count = getid3_lib::LittleEndian2Int(substr($comment_data, $comment_data_offset, 4));
        $comment_data_offset += 4;
        
        $getid3->info['avdataoffset'] = $comment_start_offset + $comment_data_offset;

        for ($i = 0; $i < $comments_count; $i++) {

            $getid3->info['ogg']['comments_raw'][$i]['dataoffset'] = $comment_start_offset + $comment_data_offset;

            if (ftell($getid3->fp) < ($getid3->info['ogg']['comments_raw'][$i]['dataoffset'] + 4)) {
                $vorbis_comment_page++;

                $ogg_page_info = $this->ParseOggPageHeader();
                $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']] = $ogg_page_info;

                // First, save what we haven't read yet
                $as_yet_unused_data = substr($comment_data, $comment_data_offset);

                // Then take that data off the end
                $comment_data = substr($comment_data, 0, $comment_data_offset);

                // Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
                $comment_data .= str_repeat("\x00", 27 + $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']]['page_segments']);
                $comment_data_offset += (27 + $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']]['page_segments']);

                // Finally, stick the unused data back on the end
                $comment_data .= $as_yet_unused_data;

                $comment_data .= fread($getid3->fp, getid3_xiph::OggPageSegmentLength($getid3->info['ogg']['pageheader'][$vorbis_comment_page], 1));
            }
            $getid3->info['ogg']['comments_raw'][$i]['size'] = getid3_lib::LittleEndian2Int(substr($comment_data, $comment_data_offset, 4));

            // replace avdataoffset with position just after the last vorbiscomment
            $getid3->info['avdataoffset'] = $getid3->info['ogg']['comments_raw'][$i]['dataoffset'] + $getid3->info['ogg']['comments_raw'][$i]['size'] + 4;

            $comment_data_offset += 4;
            while ((strlen($comment_data) - $comment_data_offset) < $getid3->info['ogg']['comments_raw'][$i]['size']) {
            
                if (($getid3->info['ogg']['comments_raw'][$i]['size'] > $getid3->info['avdataend']) || ($getid3->info['ogg']['comments_raw'][$i]['size'] < 0)) {
                    throw new getid3_exception('Invalid Ogg comment size (comment #'.$i.', claims to be '.number_format($getid3->info['ogg']['comments_raw'][$i]['size']).' bytes) - aborting reading comments');
                }

                $vorbis_comment_page++;

                $ogg_page_info = $this->ParseOggPageHeader();
                $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']] = $ogg_page_info;

                // First, save what we haven't read yet
                $as_yet_unused_data = substr($comment_data, $comment_data_offset);

                // Then take that data off the end
                $comment_data     = substr($comment_data, 0, $comment_data_offset);

                // Add [headerlength] bytes of dummy data for the Ogg Page Header, just to keep absolute offsets correct
                $comment_data .= str_repeat("\x00", 27 + $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']]['page_segments']);
                $comment_data_offset += (27 + $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']]['page_segments']);

                // Finally, stick the unused data back on the end
                $comment_data .= $as_yet_unused_data;

                //$comment_data .= fread($getid3->fp, $getid3->info['ogg']['pageheader'][$ogg_page_info['page_seqno']]['page_length']);
                $comment_data .= fread($getid3->fp, getid3_xiph::OggPageSegmentLength($getid3->info['ogg']['pageheader'][$vorbis_comment_page], 1));

                //$filebaseoffset += $ogg_page_info['header_end_offset'] - $ogg_page_info['page_start_offset'];
            }
            $comment_string = substr($comment_data, $comment_data_offset, $getid3->info['ogg']['comments_raw'][$i]['size']);
            $comment_data_offset += $getid3->info['ogg']['comments_raw'][$i]['size'];

            if (!$comment_string) {

                // no comment?
                $getid3->warning('Blank Ogg comment ['.$i.']');

            } elseif (strstr($comment_string, '=')) {

                $comment_exploded = explode('=', $comment_string, 2);
                $getid3->info['ogg']['comments_raw'][$i]['key']   = strtoupper($comment_exploded[0]);
                $getid3->info['ogg']['comments_raw'][$i]['value'] = @$comment_exploded[1];
                $getid3->info['ogg']['comments_raw'][$i]['data']  = base64_decode($getid3->info['ogg']['comments_raw'][$i]['value']);

                $getid3->info['ogg']['comments'][strtolower($getid3->info['ogg']['comments_raw'][$i]['key'])][] = $getid3->info['ogg']['comments_raw'][$i]['value'];

                if ($getid3->option_tags_images) {
                    $image_chunk_check = getid3_lib_image_size::get($getid3->info['ogg']['comments_raw'][$i]['data']);
                    $getid3->info['ogg']['comments_raw'][$i]['image_mime'] = image_type_to_mime_type($image_chunk_check[2]);
                }
                
                if (!@$getid3->info['ogg']['comments_raw'][$i]['image_mime'] || ($getid3->info['ogg']['comments_raw'][$i]['image_mime'] == 'application/octet-stream')) {
                    unset($getid3->info['ogg']['comments_raw'][$i]['image_mime']);
                    unset($getid3->info['ogg']['comments_raw'][$i]['data']);
                }
                

            } else {

                $getid3->warning('[known problem with CDex >= v1.40, < v1.50b7] Invalid Ogg comment name/value pair ['.$i.']: '.$comment_string);
            }
        }


        // Replay Gain Adjustment
        // http://privatewww.essex.ac.uk/~djmrob/replaygain/
        if (isset($getid3->info['ogg']['comments']) && is_array($getid3->info['ogg']['comments'])) {
            foreach ($getid3->info['ogg']['comments'] as $index => $commentvalue) {
                switch ($index) {
                    case 'rg_audiophile':
                    case 'replaygain_album_gain':
                        $getid3->info['replay_gain']['album']['adjustment'] = (float)$commentvalue[0];
                        unset($getid3->info['ogg']['comments'][$index]);
                        break;

                    case 'rg_radio':
                    case 'replaygain_track_gain':
                        $getid3->info['replay_gain']['track']['adjustment'] = (float)$commentvalue[0];
                        unset($getid3->info['ogg']['comments'][$index]);
                        break;

                    case 'replaygain_album_peak':
                        $getid3->info['replay_gain']['album']['peak'] = (float)$commentvalue[0];
                        unset($getid3->info['ogg']['comments'][$index]);
                        break;

                    case 'rg_peak':
                    case 'replaygain_track_peak':
                        $getid3->info['replay_gain']['track']['peak'] = (float)$commentvalue[0];
                        unset($getid3->info['ogg']['comments'][$index]);
                        break;
                        
                    case 'replaygain_reference_loudness':
                        $getid3->info['replay_gain']['reference_volume'] = (float)$commentvalue[0];
                        unset($getid3->info['ogg']['comments'][$index]);
                        break;
                }
            }
        }

        fseek($getid3->fp, $original_offset, SEEK_SET);

        return true;
    }



    private function ParseFLAC() {
        
        $getid3 = $this->getid3;
        
        // http://flac.sourceforge.net/format.html

        $getid3->info['fileformat']            = 'flac';
        $getid3->info['audio']['dataformat']   = 'flac';
        $getid3->info['audio']['bitrate_mode'] = 'vbr';
        $getid3->info['audio']['lossless']     = true;

        return $this->FLACparseMETAdata();
    }



    private function FLACparseMETAdata() {
        
        $getid3 = $this->getid3;

        do {
            
            $meta_data_block_offset    = ftell($getid3->fp);
            $meta_data_block_header    = fread($getid3->fp, 4);
            $meta_data_last_block_flag = (bool)(getid3_lib::BigEndian2Int($meta_data_block_header[0]) & 0x80);
            $meta_data_block_type      = getid3_lib::BigEndian2Int($meta_data_block_header[0]) & 0x7F;
            $meta_data_block_length    = getid3_lib::BigEndian2Int(substr($meta_data_block_header, 1, 3));
            $meta_data_block_type_text = getid3_xiph::FLACmetaBlockTypeLookup($meta_data_block_type);

            if ($meta_data_block_length < 0) {
                throw new getid3_exception('corrupt or invalid METADATA_BLOCK_HEADER.BLOCK_TYPE ('.$meta_data_block_type.') at offset '.$meta_data_block_offset);
            }

            $getid3->info['flac'][$meta_data_block_type_text]['raw'] = array (
                'offset'          => $meta_data_block_offset,
                'last_meta_block' => $meta_data_last_block_flag,
                'block_type'      => $meta_data_block_type,
                'block_type_text' => $meta_data_block_type_text,
                'block_length'    => $meta_data_block_length,
                'block_data'      => @fread($getid3->fp, $meta_data_block_length)
            );
            $getid3->info['avdataoffset'] = ftell($getid3->fp);

            switch ($meta_data_block_type_text) {

                case 'STREAMINFO':
                    if (!$this->FLACparseSTREAMINFO($getid3->info['flac'][$meta_data_block_type_text]['raw']['block_data'])) {
                        return false;
                    }
                    break;

                case 'PADDING':
                    // ignore
                    break;

                case 'APPLICATION':
                    if (!$this->FLACparseAPPLICATION($getid3->info['flac'][$meta_data_block_type_text]['raw']['block_data'])) {
                        return false;
                    }
                    break;

                case 'SEEKTABLE':
                    if (!$this->FLACparseSEEKTABLE($getid3->info['flac'][$meta_data_block_type_text]['raw']['block_data'])) {
                        return false;
                    }
                    break;

                case 'VORBIS_COMMENT':
                    $old_offset = ftell($getid3->fp);
                    fseek($getid3->fp, 0 - $meta_data_block_length, SEEK_CUR);
                    $this->ParseVorbisCommentsFilepointer($getid3->fp, $getid3->info);
                    fseek($getid3->fp, $old_offset, SEEK_SET);
                    break;

                case 'CUESHEET':
                    if (!$this->FLACparseCUESHEET($getid3->info['flac'][$meta_data_block_type_text]['raw']['block_data'])) {
                        return false;
                    }
                    break;
                    
                case 'PICTURE':
                    if (!$this->FLACparsePICTURE($getid3->info['flac'][$meta_data_block_type_text]['raw']['block_data'])) {
                        return false;
                    }
                    break;

                default:
                    $getid3->warning('Unhandled METADATA_BLOCK_HEADER.BLOCK_TYPE ('.$meta_data_block_type.') at offset '.$meta_data_block_offset);
            }

        } while ($meta_data_last_block_flag === false);


        if (isset($getid3->info['flac']['STREAMINFO'])) {
            $getid3->info['flac']['compressed_audio_bytes']   = $getid3->info['avdataend'] - $getid3->info['avdataoffset'];
            $getid3->info['flac']['uncompressed_audio_bytes'] = $getid3->info['flac']['STREAMINFO']['samples_stream'] * $getid3->info['flac']['STREAMINFO']['channels'] * ($getid3->info['flac']['STREAMINFO']['bits_per_sample'] / 8);
            $getid3->info['flac']['compression_ratio']        = $getid3->info['flac']['compressed_audio_bytes'] / $getid3->info['flac']['uncompressed_audio_bytes'];
        }

        // set md5_data_source - built into flac 0.5+
        if (isset($getid3->info['flac']['STREAMINFO']['audio_signature'])) {

            if ($getid3->info['flac']['STREAMINFO']['audio_signature'] === str_repeat("\x00", 16)) {
                $getid3->warning('FLAC STREAMINFO.audio_signature is null (known issue with libOggFLAC)');

            } else {

                $getid3->info['md5_data_source'] = '';
                $md5 = $getid3->info['flac']['STREAMINFO']['audio_signature'];
                for ($i = 0; $i < strlen($md5); $i++) {
                    $getid3->info['md5_data_source'] .= str_pad(dechex(ord($md5{$i})), 2, '00', STR_PAD_LEFT);
                }
                if (!preg_match('/^[0-9a-f]{32}$/', $getid3->info['md5_data_source'])) {
                    unset($getid3->info['md5_data_source']);
                }

            }

        }

        $getid3->info['audio']['bits_per_sample'] = $getid3->info['flac']['STREAMINFO']['bits_per_sample'];
        if ($getid3->info['audio']['bits_per_sample'] == 8) {
            // special case
            // must invert sign bit on all data bytes before MD5'ing to match FLAC's calculated value
            // MD5sum calculates on unsigned bytes, but FLAC calculated MD5 on 8-bit audio data as signed
            $getid3->warning('FLAC calculates MD5 data strangely on 8-bit audio, so the stored md5_data_source value will not match the decoded WAV file');
        }
        if (!empty($getid3->info['ogg']['vendor'])) {
            $getid3->info['audio']['encoder'] = $getid3->info['ogg']['vendor'];
        }

        return true;
    }



    private function FLACparseSTREAMINFO($meta_data_block_data) {
        
        $getid3 = $this->getid3;
        
        getid3_lib::ReadSequence('BigEndian2Int', $getid3->info['flac']['STREAMINFO'], $meta_data_block_data, 0,
            array (
                'min_block_size' => 2,
                'max_block_size' => 2,
                'min_frame_size' => 3,
                'max_frame_size' => 3
            )
        );

        $sample_rate_channels_sample_bits_stream_samples = getid3_lib::BigEndian2Bin(substr($meta_data_block_data, 10, 8));
        
        $getid3->info['flac']['STREAMINFO']['sample_rate']     = bindec(substr($sample_rate_channels_sample_bits_stream_samples,  0, 20));
        $getid3->info['flac']['STREAMINFO']['channels']        = bindec(substr($sample_rate_channels_sample_bits_stream_samples, 20,  3)) + 1;
        $getid3->info['flac']['STREAMINFO']['bits_per_sample'] = bindec(substr($sample_rate_channels_sample_bits_stream_samples, 23,  5)) + 1;
        $getid3->info['flac']['STREAMINFO']['samples_stream']  = bindec(substr($sample_rate_channels_sample_bits_stream_samples, 28, 36));      // bindec() returns float in case of int overrun
        $getid3->info['flac']['STREAMINFO']['audio_signature'] = substr($meta_data_block_data, 18, 16);

        if (!empty($getid3->info['flac']['STREAMINFO']['sample_rate'])) {

            $getid3->info['audio']['bitrate_mode']    = 'vbr';
            $getid3->info['audio']['sample_rate']     = $getid3->info['flac']['STREAMINFO']['sample_rate'];
            $getid3->info['audio']['channels']        = $getid3->info['flac']['STREAMINFO']['channels'];
            $getid3->info['audio']['bits_per_sample'] = $getid3->info['flac']['STREAMINFO']['bits_per_sample'];
            $getid3->info['playtime_seconds']         = $getid3->info['flac']['STREAMINFO']['samples_stream'] / $getid3->info['flac']['STREAMINFO']['sample_rate'];
            $getid3->info['audio']['bitrate']         = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];

        } else {

            throw new getid3_exception('Corrupt METAdata block: STREAMINFO');
        }
        
        unset($getid3->info['flac']['STREAMINFO']['raw']);

        return true;
    }



    private function FLACparseAPPLICATION($meta_data_block_data) {
        
        $getid3 = $this->getid3;
        
        $application_id = getid3_lib::BigEndian2Int(substr($meta_data_block_data, 0, 4));
        
        $getid3->info['flac']['APPLICATION'][$application_id]['name'] = getid3_xiph::FLACapplicationIDLookup($application_id);
        $getid3->info['flac']['APPLICATION'][$application_id]['data'] = substr($meta_data_block_data, 4);
        
        unset($getid3->info['flac']['APPLICATION']['raw']);

        return true;
    }



    private function FLACparseSEEKTABLE($meta_data_block_data) {
        
        $getid3 = $this->getid3;
        
        $offset = 0;
        $meta_data_block_length = strlen($meta_data_block_data);
        while ($offset < $meta_data_block_length) {
            $sample_number_string = substr($meta_data_block_data, $offset, 8);
            $offset += 8;
            if ($sample_number_string == "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF") {

                // placeholder point
                @$getid3->info['flac']['SEEKTABLE']['placeholders']++;
                $offset += 10;

            } else {

                $sample_number = getid3_lib::BigEndian2Int($sample_number_string);
                
                $getid3->info['flac']['SEEKTABLE'][$sample_number]['offset']  = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 8));
                $offset += 8;
                
                $getid3->info['flac']['SEEKTABLE'][$sample_number]['samples'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 2));
                $offset += 2;

            }
        }
        
        unset($getid3->info['flac']['SEEKTABLE']['raw']);
        
        return true;
    }



    private function FLACparseCUESHEET($meta_data_block_data) {
        
        $getid3 = $this->getid3;
        
        $getid3->info['flac']['CUESHEET']['media_catalog_number'] = trim(substr($meta_data_block_data, 0, 128), "\0");
        $getid3->info['flac']['CUESHEET']['lead_in_samples']      = getid3_lib::BigEndian2Int(substr($meta_data_block_data, 128, 8));
        $getid3->info['flac']['CUESHEET']['flags']['is_cd']       = (bool)(getid3_lib::BigEndian2Int($meta_data_block_data[136]) & 0x80);
        $getid3->info['flac']['CUESHEET']['number_tracks']        = getid3_lib::BigEndian2Int($meta_data_block_data[395]);

        $offset = 396;
        
        for ($track = 0; $track < $getid3->info['flac']['CUESHEET']['number_tracks']; $track++) {
        
            $track_sample_offset = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 8));
            $offset += 8;

            $track_number        = getid3_lib::BigEndian2Int($meta_data_block_data{$offset++});

            $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['sample_offset'] = $track_sample_offset;
            $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['isrc']          = substr($meta_data_block_data, $offset, 12);
            $offset += 12;

            $track_flags_raw = getid3_lib::BigEndian2Int($meta_data_block_data{$offset++});
            $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['flags']['is_audio']     = (bool)($track_flags_raw & 0x80);
            $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['flags']['pre_emphasis'] = (bool)($track_flags_raw & 0x40);

            $offset += 13; // reserved

            $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['index_points'] = getid3_lib::BigEndian2Int($meta_data_block_data{$offset++});

            for ($index = 0; $index < $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['index_points']; $index++) {
                
                $index_sample_offset = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 8));
                $offset += 8;
                
                $index_number = getid3_lib::BigEndian2Int($meta_data_block_data{$offset++});
                $getid3->info['flac']['CUESHEET']['tracks'][$track_number]['indexes'][$index_number] = $index_sample_offset;
                
                $offset += 3; // reserved
            }
        }
        
        unset($getid3->info['flac']['CUESHEET']['raw']);
        
        return true;
    }
    
    
    
    private function FLACparsePICTURE($meta_data_block_data) {
        
        $getid3 = $this->getid3;
        
        $picture = &$getid3->info['flac']['PICTURE'][sizeof($getid3->info['flac']['PICTURE']) - 1];
        
        $offset = 0;
        
        $picture['type'] = $this->FLACpictureTypeLookup(getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4)));
        $offset += 4;
        
        $length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['mime_type'] = substr($meta_data_block_data, $offset, $length);
        $offset += $length;
        
        $length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['description'] = substr($meta_data_block_data, $offset, $length);
        $offset += $length;
        
        $picture['width'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['height'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['color_depth'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['colors_indexed'] = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $length = getid3_lib::BigEndian2Int(substr($meta_data_block_data, $offset, 4));
        $offset += 4;
        
        $picture['image_data'] = substr($meta_data_block_data, $offset, $length);
        $offset += $length;
        
        unset($getid3->info['flac']['PICTURE']['raw']);
        
        return true;
    }
    
    
    
    public static function SpeexBandModeLookup($mode) {
        
        static $lookup = array (
            0 => 'narrow',
            1 => 'wide',
            2 => 'ultra-wide'
        );
        return (isset($lookup[$mode]) ? $lookup[$mode] : null);
    }



    public static function OggPageSegmentLength($ogg_info_array, $segment_number=1) {
        
        for ($i = 0; $i < $segment_number; $i++) {
            $segment_length = 0;
            foreach ($ogg_info_array['segment_table'] as $key => $value) {
                $segment_length += $value;
                if ($value < 255) {
                    break;
                }
            }
        }
        return $segment_length;
    }



    public static function GetQualityFromNominalBitrate($nominal_bitrate) {

        // decrease precision
        $nominal_bitrate = $nominal_bitrate / 1000;

        if ($nominal_bitrate < 128) {
            // q-1 to q4
            $qval = ($nominal_bitrate - 64) / 16;
        } elseif ($nominal_bitrate < 256) {
            // q4 to q8
            $qval = $nominal_bitrate / 32;
        } elseif ($nominal_bitrate < 320) {
            // q8 to q9
            $qval = ($nominal_bitrate + 256) / 64;
        } else {
            // q9 to q10
            $qval = ($nominal_bitrate + 1300) / 180;
        }
        return round($qval, 1); // 5 or 4.9
    }
    
    
    
    public static function FLACmetaBlockTypeLookup($block_type) {
    
        static $lookup = array (
            0 => 'STREAMINFO',
            1 => 'PADDING',
            2 => 'APPLICATION',
            3 => 'SEEKTABLE',
            4 => 'VORBIS_COMMENT',
            5 => 'CUESHEET',
            6 => 'PICTURE'
        );
        return (isset($lookup[$block_type]) ? $lookup[$block_type] : 'reserved');
    }



    public static function FLACapplicationIDLookup($application_id) {
        
        // http://flac.sourceforge.net/id.html
        
        static $lookup = array (
            0x46746F6C => 'flac-tools',                                                 // 'Ftol'
            0x46746F6C => 'Sound Font FLAC',                                            // 'SFFL'
            0x7065656D => 'Parseable Embedded Extensible Metadata (specification)',     //  'peem'
            0x786D6364 => 'xmcd'
            
        );
        return (isset($lookup[$application_id]) ? $lookup[$application_id] : 'reserved');
    }


    public static function FLACpictureTypeLookup($type_id) {
        
        static $lookup = array (
            
             0 => 'Other',
             1 => "32x32 pixels 'file icon' (PNG only)",
             2 => 'Other file icon',
             3 => 'Cover (front)',
             4 => 'Cover (back)',
             5 => 'Leaflet page',
             6 => 'Media (e.g. label side of CD)',
             7 => 'Lead artist/lead performer/soloist',
             8 => 'Artist/performer',
             9 => 'Conductor',
            10 => 'Band/Orchestra',
            11 => 'Composer',
            12 => 'Lyricist/text writer',
            13 => 'Recording Location',
            14 => 'During recording',
            15 => 'During performance',
            16 => 'Movie/video screen capture',
            17 => 'A bright coloured fish',
            18 => 'Illustration',
            19 => 'Band/artist logotype',
            20 => 'Publisher/Studio logotype'
        );
        return (isset($lookup[$type_id]) ? $lookup[$type_id] : 'reserved');
    }

}

?>