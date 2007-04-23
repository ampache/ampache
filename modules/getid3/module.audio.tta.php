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
// | module.audio.tta.php                                                 |
// | Module for analyzing TTA Audio files                                 |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.tta.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_tta extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        $getid3->info['fileformat']            = 'tta';
        $getid3->info['audio']['dataformat']   = 'tta';
        $getid3->info['audio']['lossless']     = true;
        $getid3->info['audio']['bitrate_mode'] = 'vbr';

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $tta_header = fread($getid3->fp, 26);

        $getid3->info['tta']['magic'] = 'TTA';  // Magic bytes
        
        switch ($tta_header{3}) {
        
            case "\x01": // TTA v1.x
            case "\x02": // TTA v1.x
            case "\x03": // TTA v1.x
                
                // "It was the demo-version of the TTA encoder. There is no released format with such header. TTA encoder v1 is not supported about a year."
                $getid3->info['tta']['major_version'] = 1;
                $getid3->info['avdataoffset'] += 16;
                
                getid3_lib::ReadSequence('LittleEndian2Int', $getid3->info['tta'], $tta_header, 4, 
                    array (
                        'channels'            => 2,
                        'bits_per_sample'     => 2,
                        'sample_rate'         => 4,
                        'samples_per_channel' => 4
                    )
                );                
                $getid3->info['tta']['compression_level'] = ord($tta_header{3});
                
                $getid3->info['audio']['encoder_options']   = '-e'.$getid3->info['tta']['compression_level'];
                $getid3->info['playtime_seconds']           = $getid3->info['tta']['samples_per_channel'] / $getid3->info['tta']['sample_rate'];
                break;

            case '2': // TTA v2.x
                // "I have hurried to release the TTA 2.0 encoder. Format documentation is removed from our site. This format still in development. Please wait the TTA2 format, encoder v4."
                $getid3->info['tta']['major_version'] = 2;
                $getid3->info['avdataoffset'] += 20;

                getid3_lib::ReadSequence('LittleEndian2Int', $getid3->info['tta'], $tta_header, 4, 
                    array (
                        'compression_level' => 2,
                        'audio_format'      => 2,
                        'channels'          => 2,
                        'bits_per_sample'   => 2,
                        'sample_rate'       => 4,
                        'data_length'       => 4
                    )
                );                
                
                $getid3->info['audio']['encoder_options']   = '-e'.$getid3->info['tta']['compression_level'];
                $getid3->info['playtime_seconds']           = $getid3->info['tta']['data_length'] / $getid3->info['tta']['sample_rate'];
                break;

            case '1': // TTA v3.x
                // "This is a first stable release of the TTA format. It will be supported by the encoders v3 or higher."
                $getid3->info['tta']['major_version'] = 3;
                $getid3->info['avdataoffset'] += 26;

                getid3_lib::ReadSequence('LittleEndian2Int', $getid3->info['tta'], $tta_header, 4, 
                    array (
                        'audio_format'   => 2,
                        'channels'       => 2,
                        'bits_per_sample'=> 2,
                        'sample_rate'    => 4,
                        'data_length'    => 4,
                        'crc32_footer'   => -4,     // string
                        'seek_point'     => 4 
                    )
                );                

                $getid3->info['playtime_seconds']    = $getid3->info['tta']['data_length'] / $getid3->info['tta']['sample_rate'];
                break;

            default:
                throw new getid3_exception('This version of getID3() only knows how to handle TTA v1, v2 and v3 - it may not work correctly with this file which appears to be TTA v'.$tta_header{3});
                return false;
                break;
        }

        $getid3->info['audio']['encoder']         = 'TTA v'.$getid3->info['tta']['major_version'];
        $getid3->info['audio']['bits_per_sample'] = $getid3->info['tta']['bits_per_sample'];
        $getid3->info['audio']['sample_rate']     = $getid3->info['tta']['sample_rate'];
        $getid3->info['audio']['channels']        = $getid3->info['tta']['channels'];
        $getid3->info['audio']['bitrate']         = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];

        return true;
    }

}


?>