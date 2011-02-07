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
// | module.audio.aa.php                                                  |
// | module for analyzing AA files                                        |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.aa.php,v 1.0 2009/10/16 11:07:01 jh Exp $



class getid3_aa extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $aa_header  = fread($getid3->fp, 12);

        $magic = "\x57\x90\x75\x36";
        if (substr($aa_header, 4, 4) != $magic) {
            $getid3->error('expecting '.$magic.' at '.$getid3->info['avdataoffset'].' but found '.substr($aa_header, 4, 4));
        	return false;
        }

        $getid3->info['aa'] = array();
        $info_aa = &$getid3->info['aa'];

        $getid3->info['fileformat']            = 'aa';
        $getid3->info['audio']['dataformat']   = 'aa';
        $getid3->info['audio']['bitrate_mode'] = 'cbr'; // is it?
        $info_aa['encoding']                   = 'ISO-8859-1';

        $info_aa['filesize'] = getid3_lib::BigEndian2Int(substr($aa_header,  0, 4));
        if ($info_aa['filesize'] > ($getid3->info['avdataend'] - $getid3->info['avdataoffset'])) {
            $getid3->warning('Possible truncated file - expecting "'.$info_aa['filesize'].'" bytes of data, only found '.($getid3->info['avdataend'] - $getid3->info['avdataoffset']).' bytes"');
        }

        $info_aa['toc_size'] = getid3_lib::BigEndian2Int(substr($aa_header,  8, 4));
        $info_aa['table_size'] = $info_aa['toc_size'] * 3;

        //$aa_header .= fread($getid3->fp, $info_aa['header_length'] - 8);
        //$getid3->info['avdataoffset'] += $info_aa['header_length'];

        //getid3_lib::ReadSequence('BigEndian2Int', $info_aa, $aa_header, 8,
        //    array (
        //        'data_size'     => 4,
        //        'data_format_id'=> 4,
        //        'sample_rate'   => 4,
        //        'channels'      => 4
        //    )
        //);
        //$info_aa['comments']['comment'][] = trim(substr($aa_header, 24));
        //
        //$info_aa['data_format']          = getid3_au::AUdataFormatNameLookup($info_aa['data_format_id']);
        //$info_aa['used_bits_per_sample'] = getid3_au::AUdataFormatUsedBitsPerSampleLookup($info_aa['data_format_id']);
        //if ($info_aa['bits_per_sample']  = getid3_au::AUdataFormatBitsPerSampleLookup($info_aa['data_format_id'])) {
        //    $getid3->info['audio']['bits_per_sample'] = $info_aa['bits_per_sample'];
        //} else {
        //    unset($info_aa['bits_per_sample']);
        //}
        //
        //$getid3->info['audio']['sample_rate'] = $info_aa['sample_rate'];
        //$getid3->info['audio']['channels']    = $info_aa['channels'];

        //$getid3->info['playtime_seconds'] = $info_aa['data_size'] / ($info_aa['sample_rate'] * $info_aa['channels'] * ($info_aa['used_bits_per_sample'] / 8));
        //$getid3->info['audio']['bitrate'] = ($info_aa['data_size'] * 8) / $getid3->info['playtime_seconds'];

        return true;
    }

}


?>