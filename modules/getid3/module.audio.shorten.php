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
// | module.audio.shorten.php                                             |
// | Module for analyzing Shorten Audio files                             |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.shorten.php,v 1.5 2006/12/03 19:28:18 ah Exp $



class getid3_shorten extends getid3_handler
{

    public function __construct(getID3 $getid3) {

        parent::__construct($getid3);

        if (preg_match('#(1|ON)#i', ini_get('safe_mode'))) {
            throw new getid3_exception('PHP running in Safe Mode - backtick operator not available, cannot analyze Shorten files.');
        }

        if (!`head --version`) {
            throw new getid3_exception('head[.exe] binary not found in path. UNIX: typically /usr/bin. Windows: typically c:\windows\system32.');
        }

        if (!`shorten -l`) {
            throw new getid3_exception('shorten[.exe] binary not found in path. UNIX: typically /usr/bin. Windows: typically c:\windows\system32.');
        }
    }


    public function Analyze() {

        $getid3 = $this->getid3;

        $getid3->include_module('audio-video.riff');

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);

        $shn_header = fread($getid3->fp, 8);

        // Magic bytes: "ajkg"

        $getid3->info['fileformat']            = 'shn';
        $getid3->info['audio']['dataformat']   = 'shn';
        $getid3->info['audio']['lossless']     = true;
        $getid3->info['audio']['bitrate_mode'] = 'vbr';

        $getid3->info['shn']['version'] = getid3_lib::LittleEndian2Int($shn_header{4});

        fseek($getid3->fp, $getid3->info['avdataend'] - 12, SEEK_SET);

        $seek_table_signature_test = fread($getid3->fp, 12);

        $getid3->info['shn']['seektable']['present'] = (bool)(substr($seek_table_signature_test, 4, 8) == 'SHNAMPSK');
        if ($getid3->info['shn']['seektable']['present']) {

            $getid3->info['shn']['seektable']['length'] = getid3_lib::LittleEndian2Int(substr($seek_table_signature_test, 0, 4));
            $getid3->info['shn']['seektable']['offset'] = $getid3->info['avdataend'] - $getid3->info['shn']['seektable']['length'];
            fseek($getid3->fp, $getid3->info['shn']['seektable']['offset'], SEEK_SET);
            $seek_table_magic = fread($getid3->fp, 4);

            if ($seek_table_magic != 'SEEK') {

                throw new getid3_exception('Expecting "SEEK" at offset '.$getid3->info['shn']['seektable']['offset'].', found "'.$seek_table_magic.'"');
            }

            $seek_table_data = fread($getid3->fp, $getid3->info['shn']['seektable']['length'] - 16);
            $getid3->info['shn']['seektable']['entry_count'] = floor(strlen($seek_table_data) / 80);
        }

        $commandline = 'shorten -x '.escapeshellarg(realpath($getid3->filename)).' - | head -c 64';
        $output = `$commandline`;

        if (@$output && substr($output, 12, 4) == 'fmt ') {

            $fmt_size = getid3_lib::LittleEndian2Int(substr($output, 16, 4));
            $decoded_wav_format_ex = getid3_riff::RIFFparseWAVEFORMATex(substr($output, 20, $fmt_size));

            $getid3->info['audio']['channels']        = $decoded_wav_format_ex['channels'];
            $getid3->info['audio']['bits_per_sample'] = $decoded_wav_format_ex['bits_per_sample'];
            $getid3->info['audio']['sample_rate']     = $decoded_wav_format_ex['sample_rate'];

            if (substr($output, 20 + $fmt_size, 4) == 'data') {

                $getid3->info['playtime_seconds'] = getid3_lib::LittleEndian2Int(substr($output, 20 + 4 + $fmt_size, 4)) / $decoded_wav_format_ex['raw']['nAvgBytesPerSec'];

            } else {

                throw new getid3_exception('shorten failed to decode DATA chunk to expected location, cannot determine playtime');
            }

            $getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) / $getid3->info['playtime_seconds']) * 8;

        } else {

            throw new getid3_exception('shorten failed to decode file to WAV for parsing');
            return false;
        }

        return true;
    }

}

?>