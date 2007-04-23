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
// | module.audio.wavpack.php                                             |
// | module for analyzing WavPack v4.0+ Audio files                       |
// | dependencies: audio-video.riff                                       |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.wavpack.php,v 1.2 2006/11/02 10:48:02 ah Exp $


class getid3_wavpack extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');
        
        $getid3->info['wavpack'] = array ();
        $info_wavpack = &$getid3->info['wavpack'];
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);

        while (true) {

            $wavpack_header = fread($getid3->fp, 32);

            if (ftell($getid3->fp) >= $getid3->info['avdataend']) {
                break;
            } elseif (feof($getid3->fp)) {
                break;
            } elseif (
                (@$info_wavpack_blockheader['total_samples'] > 0) &&
                (@$info_wavpack_blockheader['block_samples'] > 0) &&
                (!isset($info_wavpack['riff_trailer_size']) || ($info_wavpack['riff_trailer_size'] <= 0)) &&
                ((@$info_wavpack['config_flags']['md5_checksum'] === false) || !empty($getid3->info['md5_data_source']))) {
                    break;
            }

            $block_header_offset = ftell($getid3->fp) - 32;
            $block_header_magic  =                              substr($wavpack_header, 0, 4);
            $block_header_size   = getid3_lib::LittleEndian2Int(substr($wavpack_header, 4, 4));

            if ($block_header_magic != 'wvpk') {
                throw new getid3_exception('Expecting "wvpk" at offset '.$block_header_offset.', found "'.$block_header_magic.'"');
            }

            if ((@$info_wavpack_blockheader['block_samples'] <= 0)  ||  (@$info_wavpack_blockheader['total_samples'] <= 0)) {
                
                // Also, it is possible that the first block might not have
                // any samples (block_samples == 0) and in this case you should skip blocks
                // until you find one with samples because the other information (like
                // total_samples) are not guaranteed to be correct until (block_samples > 0)

                // Finally, I have defined a format for files in which the length is not known
                // (for example when raw files are created using pipes). In these cases
                // total_samples will be -1 and you must seek to the final block to determine
                // the total number of samples.


                $getid3->info['audio']['dataformat']   = 'wavpack';
                $getid3->info['fileformat']            = 'wavpack';
                $getid3->info['audio']['lossless']     = true;
                $getid3->info['audio']['bitrate_mode'] = 'vbr';

                $info_wavpack['blockheader']['offset'] = $block_header_offset;
                $info_wavpack['blockheader']['magic']  = $block_header_magic;
                $info_wavpack['blockheader']['size']   = $block_header_size;
                $info_wavpack_blockheader = &$info_wavpack['blockheader'];

                if ($info_wavpack_blockheader['size'] >= 0x100000) {
                    throw new getid3_exception('Expecting WavPack block size less than "0x100000", found "'.$info_wavpack_blockheader['size'].'" at offset '.$info_wavpack_blockheader['offset']);
                }

                $info_wavpack_blockheader['minor_version'] = ord($wavpack_header{8});
                $info_wavpack_blockheader['major_version'] = ord($wavpack_header{9});

                if (($info_wavpack_blockheader['major_version'] != 4) ||
                    (($info_wavpack_blockheader['minor_version'] < 4) &&
                    ($info_wavpack_blockheader['minor_version'] > 16))) {
                    throw new getid3_exception('Expecting WavPack version between "4.2" and "4.16", found version "'.$info_wavpack_blockheader['major_version'].'.'.$info_wavpack_blockheader['minor_version'].'" at offset '.$info_wavpack_blockheader['offset']);
                }

                $info_wavpack_blockheader['track_number']  = ord($wavpack_header{10}); // unused
                $info_wavpack_blockheader['index_number']  = ord($wavpack_header{11}); // unused
                
                getid3_lib::ReadSequence('LittleEndian2Int', $info_wavpack_blockheader, $wavpack_header, 12,
                    array (
                        'total_samples' => 4,
                        'block_index'   => 4,
                        'block_samples' => 4,
                        'flags_raw'     => 4,
                        'crc'           => 4
                    )
                );
                
                
                $info_wavpack_blockheader['flags']['bytes_per_sample']     =    1 + ($info_wavpack_blockheader['flags_raw'] & 0x00000003);
                $info_wavpack_blockheader['flags']['mono']                 = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000004);
                $info_wavpack_blockheader['flags']['hybrid']               = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000008);
                $info_wavpack_blockheader['flags']['joint_stereo']         = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000010);
                $info_wavpack_blockheader['flags']['cross_decorrelation']  = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000020);
                $info_wavpack_blockheader['flags']['hybrid_noiseshape']    = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000040);
                $info_wavpack_blockheader['flags']['ieee_32bit_float']     = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000080);
                $info_wavpack_blockheader['flags']['int_32bit']            = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000100);
                $info_wavpack_blockheader['flags']['hybrid_bitrate_noise'] = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000200);
                $info_wavpack_blockheader['flags']['hybrid_balance_noise'] = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000400);
                $info_wavpack_blockheader['flags']['multichannel_initial'] = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00000800);
                $info_wavpack_blockheader['flags']['multichannel_final']   = (bool) ($info_wavpack_blockheader['flags_raw'] & 0x00001000);

                $getid3->info['audio']['lossless'] = !$info_wavpack_blockheader['flags']['hybrid'];
            }


            while (!feof($getid3->fp) && (ftell($getid3->fp) < ($block_header_offset + $block_header_size + 8))) {

                $metablock = array('offset'=>ftell($getid3->fp));
                $metablockheader = fread($getid3->fp, 2);
                if (feof($getid3->fp)) {
                    break;
                }
                $metablock['id'] = ord($metablockheader{0});
                $metablock['function_id'] = ($metablock['id'] & 0x3F);
                $metablock['function_name'] = $this->WavPackMetablockNameLookup($metablock['function_id']);

                // The 0x20 bit in the id of the meta subblocks (which is defined as
                // ID_OPTIONAL_DATA) is a permanent part of the id. The idea is that
                // if a decoder encounters an id that it does not know about, it uses
                // that "ID_OPTIONAL_DATA" flag to determine what to do. If it is set
                // then the decoder simply ignores the metadata, but if it is zero
                // then the decoder should quit because it means that an understanding
                // of the metadata is required to correctly decode the audio.
                
                $metablock['non_decoder'] = (bool) ($metablock['id'] & 0x20);
                $metablock['padded_data'] = (bool) ($metablock['id'] & 0x40);
                $metablock['large_block'] = (bool) ($metablock['id'] & 0x80);
                if ($metablock['large_block']) {
                    $metablockheader .= fread($getid3->fp, 2);
                }
                $metablock['size'] = getid3_lib::LittleEndian2Int(substr($metablockheader, 1)) * 2; // size is stored in words
                $metablock['data'] = null;

                if ($metablock['size'] > 0) {

                    switch ($metablock['function_id']) {

                        case 0x21: // ID_RIFF_HEADER
                        case 0x22: // ID_RIFF_TRAILER
                        case 0x23: // ID_REPLAY_GAIN
                        case 0x24: // ID_CUESHEET
                        case 0x25: // ID_CONFIG_BLOCK
                        case 0x26: // ID_MD5_CHECKSUM
                            $metablock['data'] = fread($getid3->fp, $metablock['size']);

                            if ($metablock['padded_data']) {
                                // padded to the nearest even byte
                                $metablock['size']--;
                                $metablock['data'] = substr($metablock['data'], 0, -1);
                            }
                            break;


                        case 0x00: // ID_DUMMY
                        case 0x01: // ID_ENCODER_INFO
                        case 0x02: // ID_DECORR_TERMS
                        case 0x03: // ID_DECORR_WEIGHTS
                        case 0x04: // ID_DECORR_SAMPLES
                        case 0x05: // ID_ENTROPY_VARS
                        case 0x06: // ID_HYBRID_PROFILE
                        case 0x07: // ID_SHAPING_WEIGHTS
                        case 0x08: // ID_FLOAT_INFO
                        case 0x09: // ID_INT32_INFO
                        case 0x0A: // ID_WV_BITSTREAM
                        case 0x0B: // ID_WVC_BITSTREAM
                        case 0x0C: // ID_WVX_BITSTREAM
                        case 0x0D: // ID_CHANNEL_INFO
                            fseek($getid3->fp, $metablock['offset'] + ($metablock['large_block'] ? 4 : 2) + $metablock['size'], SEEK_SET);
                            break;


                        default:
                            $getid3->warning('Unexpected metablock type "0x'.str_pad(dechex($metablock['function_id']), 2, '0', STR_PAD_LEFT).'" at offset '.$metablock['offset']);
                            fseek($getid3->fp, $metablock['offset'] + ($metablock['large_block'] ? 4 : 2) + $metablock['size'], SEEK_SET);
                            break;
                    }


                    switch ($metablock['function_id']) {

                        case 0x21: // ID_RIFF_HEADER
                            
                            $original_wav_filesize = getid3_lib::LittleEndian2Int(substr($metablock['data'], 4, 4));
                            
                            // Clone getid3 
                            $clone = clone $getid3;
                            
                            // Analyze clone by string
                            $riff = new getid3_riff($clone);
                            $riff->AnalyzeString($metablock['data']);
                            
                            // Import from clone and destroy
                            $metablock['riff'] = $clone->info['riff'];
                            $getid3->warnings($clone->warnings());
                            unset($clone);
                            
                            // Save RIFF header - we may need it later for RIFF footer parsing
                            $this->riff_header = $metablock['data'];
                            
                            $metablock['riff']['original_filesize'] = $original_wav_filesize;
                            $info_wavpack['riff_trailer_size'] = $original_wav_filesize - $metablock['riff']['WAVE']['data'][0]['size'] - $metablock['riff']['header_size'];

                            $getid3->info['audio']['sample_rate'] = $metablock['riff']['raw']['fmt ']['nSamplesPerSec'];
                            $getid3->info['playtime_seconds']     = $info_wavpack_blockheader['total_samples'] / $getid3->info['audio']['sample_rate'];

                            // Safe RIFF header in case there's a RIFF footer later
                            $metablock_riff_header = $metablock['data'];
                            break;


                        case 0x22: // ID_RIFF_TRAILER

                            $metablock_riff_footer = $metablock_riff_header.$metablock['data'];
                            
                            $start_offset = $metablock['offset'] + ($metablock['large_block'] ? 4 : 2);
                            
                            $ftell_old = ftell($getid3->fp);
                            
                            // Clone getid3 
                            $clone = clone $getid3;
                            
                            // Call public method that really should be private
                            $riff = new getid3_riff($clone);
                            $metablock['riff'] = $riff->ParseRIFF($start_offset, $start_offset + $metablock['size']);
                            unset($clone);
                            
                            fseek($getid3->fp, $ftell_old, SEEK_SET);

                            if (!empty($metablock['riff']['INFO'])) {
                                getid3_riff::RIFFCommentsParse($metablock['riff']['INFO'], $metablock['comments']);
                                $getid3->info['tags']['riff'] = $metablock['comments'];
                            }
                            break;


                        case 0x23: // ID_REPLAY_GAIN
                            $getid3->warning('WavPack "Replay Gain" contents not yet handled by getID3() in metablock at offset '.$metablock['offset']);
                            break;


                        case 0x24: // ID_CUESHEET
                            $getid3->warning('WavPack "Cuesheet" contents not yet handled by getID3() in metablock at offset '.$metablock['offset']);
                            break;


                        case 0x25: // ID_CONFIG_BLOCK
                            $metablock['flags_raw'] = getid3_lib::LittleEndian2Int(substr($metablock['data'], 0, 3));

                            $metablock['flags']['adobe_mode']     = (bool) ($metablock['flags_raw'] & 0x000001); // "adobe" mode for 32-bit floats
                            $metablock['flags']['fast_flag']      = (bool) ($metablock['flags_raw'] & 0x000002); // fast mode
                            $metablock['flags']['very_fast_flag'] = (bool) ($metablock['flags_raw'] & 0x000004); // double fast
                            $metablock['flags']['high_flag']      = (bool) ($metablock['flags_raw'] & 0x000008); // high quality mode
                            $metablock['flags']['very_high_flag'] = (bool) ($metablock['flags_raw'] & 0x000010); // double high (not used yet)
                            $metablock['flags']['bitrate_kbps']   = (bool) ($metablock['flags_raw'] & 0x000020); // bitrate is kbps, not bits / sample
                            $metablock['flags']['auto_shaping']   = (bool) ($metablock['flags_raw'] & 0x000040); // automatic noise shaping
                            $metablock['flags']['shape_override'] = (bool) ($metablock['flags_raw'] & 0x000080); // shaping mode specified
                            $metablock['flags']['joint_override'] = (bool) ($metablock['flags_raw'] & 0x000100); // joint-stereo mode specified
                            $metablock['flags']['copy_time']      = (bool) ($metablock['flags_raw'] & 0x000200); // copy file-time from source
                            $metablock['flags']['create_exe']     = (bool) ($metablock['flags_raw'] & 0x000400); // create executable
                            $metablock['flags']['create_wvc']     = (bool) ($metablock['flags_raw'] & 0x000800); // create correction file
                            $metablock['flags']['optimize_wvc']   = (bool) ($metablock['flags_raw'] & 0x001000); // maximize bybrid compression
                            $metablock['flags']['quality_mode']   = (bool) ($metablock['flags_raw'] & 0x002000); // psychoacoustic quality mode
                            $metablock['flags']['raw_flag']       = (bool) ($metablock['flags_raw'] & 0x004000); // raw mode (not implemented yet)
                            $metablock['flags']['calc_noise']     = (bool) ($metablock['flags_raw'] & 0x008000); // calc noise in hybrid mode
                            $metablock['flags']['lossy_mode']     = (bool) ($metablock['flags_raw'] & 0x010000); // obsolete (for information)
                            $metablock['flags']['extra_mode']     = (bool) ($metablock['flags_raw'] & 0x020000); // extra processing mode
                            $metablock['flags']['skip_wvx']       = (bool) ($metablock['flags_raw'] & 0x040000); // no wvx stream w/ floats & big ints
                            $metablock['flags']['md5_checksum']   = (bool) ($metablock['flags_raw'] & 0x080000); // compute & store MD5 signature
                            $metablock['flags']['quiet_mode']     = (bool) ($metablock['flags_raw'] & 0x100000); // don't report progress %

                            $info_wavpack['config_flags'] = $metablock['flags'];

                            $getid3->info['audio']['encoder_options'] = trim(
                                ($info_wavpack_blockheader['flags']['hybrid'] ? ' -b???' : '') .
                                ($metablock['flags']['adobe_mode']            ? ' -a'    : '') .
                                ($metablock['flags']['optimize_wvc']          ? ' -cc'   : '') .
                                ($metablock['flags']['create_exe']            ? ' -e'    : '') .
                                ($metablock['flags']['fast_flag']             ? ' -f'    : '') .
                                ($metablock['flags']['joint_override']        ? ' -j?'   : '') .
                                ($metablock['flags']['high_flag']             ? ' -h'    : '') .
                                ($metablock['flags']['md5_checksum']          ? ' -m'    : '') .
                                ($metablock['flags']['calc_noise']            ? ' -n'    : '') .
                                ($metablock['flags']['shape_override']        ? ' -s?'   : '') .
                                ($metablock['flags']['extra_mode']            ? ' -x?'   : '')
                            );
                            if (!$getid3->info['audio']['encoder_options']) {
                                unset($getid3->info['audio']['encoder_options']);
                            }
                            break;


                        case 0x26: // ID_MD5_CHECKSUM
                            if (strlen($metablock['data']) == 16) {
                                $getid3->info['md5_data_source'] = strtolower(getid3_lib::PrintHexBytes($metablock['data'], true, false, false));
                            } else {
                                $getid3->warning('Expecting 16 bytes of WavPack "MD5 Checksum" in metablock at offset '.$metablock['offset'].', but found '.strlen($metablock['data']).' bytes');
                            }
                            break;


                        case 0x00: // ID_DUMMY
                        case 0x01: // ID_ENCODER_INFO
                        case 0x02: // ID_DECORR_TERMS
                        case 0x03: // ID_DECORR_WEIGHTS
                        case 0x04: // ID_DECORR_SAMPLES
                        case 0x05: // ID_ENTROPY_VARS
                        case 0x06: // ID_HYBRID_PROFILE
                        case 0x07: // ID_SHAPING_WEIGHTS
                        case 0x08: // ID_FLOAT_INFO
                        case 0x09: // ID_INT32_INFO
                        case 0x0A: // ID_WV_BITSTREAM
                        case 0x0B: // ID_WVC_BITSTREAM
                        case 0x0C: // ID_WVX_BITSTREAM
                        case 0x0D: // ID_CHANNEL_INFO
                            unset($metablock);
                            break;
                    }

                }
            
                if (!empty($metablock)) {
                    $info_wavpack['metablocks'][] = $metablock;
                }

            }

        }

        $getid3->info['audio']['encoder']         = 'WavPack v'.$info_wavpack_blockheader['major_version'].'.'.str_pad($info_wavpack_blockheader['minor_version'], 2, '0', STR_PAD_LEFT);
        $getid3->info['audio']['bits_per_sample'] = $info_wavpack_blockheader['flags']['bytes_per_sample'] * 8;
        $getid3->info['audio']['channels']        = ($info_wavpack_blockheader['flags']['mono'] ? 1 : 2);

        if (@$getid3->info['playtime_seconds']) {
            $getid3->info['audio']['bitrate']     = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
        } else {
            $getid3->info['audio']['dataformat']  = 'wvc';
        }

        return true;
    }



    public static function WavPackMetablockNameLookup($id) {

        static $lookup = array(
            0x00 => 'Dummy',
            0x01 => 'Encoder Info',
            0x02 => 'Decorrelation Terms',
            0x03 => 'Decorrelation Weights',
            0x04 => 'Decorrelation Samples',
            0x05 => 'Entropy Variables',
            0x06 => 'Hybrid Profile',
            0x07 => 'Shaping Weights',
            0x08 => 'Float Info',
            0x09 => 'Int32 Info',
            0x0A => 'WV Bitstream',
            0x0B => 'WVC Bitstream',
            0x0C => 'WVX Bitstream',
            0x0D => 'Channel Info',
            0x21 => 'RIFF header',
            0x22 => 'RIFF trailer',
            0x23 => 'Replay Gain',
            0x24 => 'Cuesheet',
            0x25 => 'Config Block',
            0x26 => 'MD5 Checksum',
        );
        
        return (@$lookup[$id]);
    }

}


?>