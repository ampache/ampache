<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 James Heinrich, Allan Hansen                 |
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
// | module.audio.vqf.php                                                 |
// | Module for analyzing VQF Audio files                                 |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.vqf.php,v 1.3 2006/11/16 23:16:31 ah Exp $



class getid3_vqf extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        // based loosely on code from TTwinVQ by Jurgen Faul <jfaulØgmx*de>
        // http://jfaul.de/atl  or  http://j-faul.virtualave.net/atl/atl.html

        $getid3->info['fileformat']            = 'vqf';
        $getid3->info['audio']['dataformat']   = 'vqf';
        $getid3->info['audio']['bitrate_mode'] = 'cbr';
        $getid3->info['audio']['lossless']     = false;

        // Shortcuts
        $getid3->info['vqf']['raw'] = array ();
        $info_vqf      = &$getid3->info['vqf'];
        $info_vqf_raw  = &$info_vqf['raw'];

        // Get header
        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $vqf_header_data = fread($getid3->fp, 16);

        $info_vqf_raw['header_tag'] = 'TWIN';   // Magic bytes
        $info_vqf_raw['version']    = substr($vqf_header_data, 4, 8);
        $info_vqf_raw['size']       = getid3_lib::BigEndian2Int(substr($vqf_header_data, 12, 4));

        while (ftell($getid3->fp) < $getid3->info['avdataend']) {

            $chunk_base_offset = ftell($getid3->fp);
            $chunk_data        = fread($getid3->fp, 8);
            $chunk_name        = substr($chunk_data, 0, 4);

            if ($chunk_name == 'DATA') {
                $getid3->info['avdataoffset'] = $chunk_base_offset;
                break;
            }

            $chunk_size = getid3_lib::BigEndian2Int(substr($chunk_data, 4, 4));
            if ($chunk_size > ($getid3->info['avdataend'] - ftell($getid3->fp))) {
                throw new getid3_exception('Invalid chunk size ('.$chunk_size.') for chunk "'.$chunk_name.'" at offset 8.');
            }
            if ($chunk_size > 0) {
                $chunk_data .= fread($getid3->fp, $chunk_size);
            }

            switch ($chunk_name) {

                case 'COMM':
                    $info_vqf['COMM'] = array ();
                    getid3_lib::ReadSequence('BigEndian2Int', $info_vqf['COMM'], $chunk_data, 8,
                        array (
                            'channel_mode'   => 4,
                            'bitrate'        => 4,
                            'sample_rate'    => 4,
                            'security_level' => 4
                        )
                    );

                    $getid3->info['audio']['channels']        = $info_vqf['COMM']['channel_mode'] + 1;
                    $getid3->info['audio']['sample_rate']     = getid3_vqf::VQFchannelFrequencyLookup($info_vqf['COMM']['sample_rate']);
                    $getid3->info['audio']['bitrate']         = $info_vqf['COMM']['bitrate'] * 1000;
                    $getid3->info['audio']['encoder_options'] = 'CBR' . ceil($getid3->info['audio']['bitrate']/1000);

                    if ($getid3->info['audio']['bitrate'] == 0) {
                        throw new getid3_exception('Corrupt VQF file: bitrate_audio == zero');
                    }
                    break;

                case 'NAME':
                case 'AUTH':
                case '(c) ':
                case 'FILE':
                case 'COMT':
                case 'ALBM':
                    $info_vqf['comments'][getid3_vqf::VQFcommentNiceNameLookup($chunk_name)][] = trim(substr($chunk_data, 8));
                    break;

                case 'DSIZ':
                    $info_vqf['DSIZ'] = getid3_lib::BigEndian2Int(substr($chunk_data, 8, 4));
                    break;

                default:
                    $getid3->warning('Unhandled chunk type "'.$chunk_name.'" at offset 8');
                    break;
            }
        }

        $getid3->info['playtime_seconds'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['audio']['bitrate'];

        if (isset($info_vqf['DSIZ']) && (($info_vqf['DSIZ'] != ($getid3->info['avdataend'] - $getid3->info['avdataoffset'] - strlen('DATA'))))) {
            switch ($info_vqf['DSIZ']) {
                case 0:
                case 1:
                    $getid3->warning('Invalid DSIZ value "'.$info_vqf['DSIZ'].'". This is known to happen with VQF files encoded by Ahead Nero, and seems to be its way of saying this is TwinVQF v'.($info_vqf['DSIZ'] + 1).'.0');
                    $getid3->info['audio']['encoder'] = 'Ahead Nero';
                    break;

                default:
                    $getid3->warning('Probable corrupted file - should be '.$info_vqf['DSIZ'].' bytes, actually '.($getid3->info['avdataend'] - $getid3->info['avdataoffset'] - strlen('DATA')));
                    break;
            }
        }

        return true;
    }



    public static function VQFchannelFrequencyLookup($frequencyid) {

        static $lookup = array (
            11 => 11025,
            22 => 22050,
            44 => 44100
        );
        return (isset($lookup[$frequencyid]) ? $lookup[$frequencyid] : $frequencyid * 1000);
    }



    public static function VQFcommentNiceNameLookup($shortname) {

        static $lookup = array (
            'NAME' => 'title',
            'AUTH' => 'artist',
            '(c) ' => 'copyright',
            'FILE' => 'filename',
            'COMT' => 'comment',
            'ALBM' => 'album'
        );
        return (isset($lookup[$shortname]) ? $lookup[$shortname] : $shortname);
    }

}


?>