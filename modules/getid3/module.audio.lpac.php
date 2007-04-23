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
// | module.audio.lpac.php                                                |
// | Module for analyzing LPAC Audio files                                |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.lpac.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_lpac extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');
        
        // Magic bytes - 'LPAC'
        
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $lpac_header = fread($getid3->fp, 14);
        
        $getid3->info['avdataoffset'] += 14;
        
        $getid3->info['lpac'] = array ();
        $info_lpac = &$getid3->info['lpac'];

        $getid3->info['fileformat']               = 'lpac';
        $getid3->info['audio']['dataformat']      = 'lpac';
        $getid3->info['audio']['lossless']        = true;
        $getid3->info['audio']['bitrate_mode']    = 'vbr';
                                                  
        $info_lpac['file_version']     = getid3_lib::BigEndian2Int($lpac_header{4});
        $flags['audio_type']                      = getid3_lib::BigEndian2Int($lpac_header{5});
        $info_lpac['total_samples']    = getid3_lib::BigEndian2Int(substr($lpac_header,  6, 4));
        $flags['parameters']                      = getid3_lib::BigEndian2Int(substr($lpac_header, 10, 4));

        $info_lpac['flags']['is_wave'] = (bool)($flags['audio_type'] & 0x40);
        $info_lpac['flags']['stereo']  = (bool)($flags['audio_type'] & 0x04);
        $info_lpac['flags']['24_bit']  = (bool)($flags['audio_type'] & 0x02);
        $info_lpac['flags']['16_bit']  = (bool)($flags['audio_type'] & 0x01);

        if ($info_lpac['flags']['24_bit'] && $info_lpac['flags']['16_bit']) {
            $getid3->warning('24-bit and 16-bit flags cannot both be set');
        }

        $info_lpac['flags']['fast_compress']             =   (bool)($flags['parameters'] & 0x40000000);
        $info_lpac['flags']['random_access']             =   (bool)($flags['parameters'] & 0x08000000);
        $info_lpac['block_length']                       = pow(2, (($flags['parameters'] & 0x07000000) >> 24)) * 256;
        $info_lpac['flags']['adaptive_prediction_order'] =   (bool)($flags['parameters'] & 0x00800000);
        $info_lpac['flags']['adaptive_quantization']     =   (bool)($flags['parameters'] & 0x00400000);
        $info_lpac['flags']['joint_stereo']              =   (bool)($flags['parameters'] & 0x00040000);
        $info_lpac['quantization']                       =         ($flags['parameters'] & 0x00001F00) >> 8;
        $info_lpac['max_prediction_order']               =         ($flags['parameters'] & 0x0000003F);

        if ($info_lpac['flags']['fast_compress'] && ($info_lpac['max_prediction_order'] != 3)) {
            $getid3->warning('max_prediction_order expected to be "3" if fast_compress is true, actual value is "'.$info_lpac['max_prediction_order'].'"');
        }
        
        switch ($info_lpac['file_version']) {
        
            case 6:
                if ($info_lpac['flags']['adaptive_quantization']) {
                    $getid3->warning('adaptive_quantization expected to be false in LPAC file stucture v6, actually true');
                }
                if ($info_lpac['quantization'] != 20) {
                    $getid3->warning('Quantization expected to be 20 in LPAC file stucture v6, actually '.$info_lpac['flags']['Q']);
                }
                break;

        
            default:
                //$getid3->warning('This version of getID3() only supports LPAC file format version 6, this file is version '.$info_lpac['file_version'].' - please report to info@getid3.org');
                break;
        }

        // Clone getid3 - messing with something - better safe than sorry
        $clone = clone $getid3;
        
        // Analyze clone by fp
        $riff = new getid3_riff($clone);
        $riff->Analyze();
        
        // Import from clone and destroy
        $getid3->info['avdataoffset']                = $clone->info['avdataoffset'];
        $getid3->info['riff']                        = $clone->info['riff'];
        //$info_lpac['comments']['comment'] = $clone->info['comments'];
        $getid3->info['audio']['sample_rate']        = $clone->info['audio']['sample_rate'];
        $getid3->warnings($clone->warnings());
        unset($clone);
        
        $getid3->info['audio']['channels'] = ($info_lpac['flags']['stereo'] ? 2 : 1);

        if ($info_lpac['flags']['24_bit']) {
            $getid3->info['audio']['bits_per_sample'] = $getid3->info['riff']['audio'][0]['bits_per_sample'];
        } elseif ($info_lpac['flags']['16_bit']) {
            $getid3->info['audio']['bits_per_sample'] = 16;
        } else {
            $getid3->info['audio']['bits_per_sample'] = 8;
        }

        if ($info_lpac['flags']['fast_compress']) {
             // fast
            $getid3->info['audio']['encoder_options'] = '-1';
        } else {
            switch ($info_lpac['max_prediction_order']) {
                case 20: // simple
                    $getid3->info['audio']['encoder_options'] = '-2';
                    break;
                case 30: // medium
                    $getid3->info['audio']['encoder_options'] = '-3';
                    break;
                case 40: // high
                    $getid3->info['audio']['encoder_options'] = '-4';
                    break;
                case 60: // extrahigh
                    $getid3->info['audio']['encoder_options'] = '-5';
                    break;
            }
        }

        $getid3->info['playtime_seconds'] = $info_lpac['total_samples'] / $getid3->info['audio']['sample_rate'];
        $getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];

        return true;
    }

}


?>