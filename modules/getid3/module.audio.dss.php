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
// | module.audio.dss.php                                                 |
// | module for analyzing Digital Speech Standard (DSS) files             |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+


class getid3_dss extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

		fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
		$DSSheader  = fread($getid3->fp, 1256);

		if (substr($DSSheader, 0, 4) != "\x02".'dss') {
			$getid3->info['error'][] = 'Expecting "[x02]dss" at offset '.$getid3->info['avdataoffset'].', found "'.substr($DSSheader, 0, 4).'"';
			return false;
		}

		// some structure information taken from http://cpansearch.perl.org/src/RGIBSON/Audio-DSS-0.02/lib/Audio/DSS.pm

		// shortcut
		$getid3->info['dss'] = array();
		$thisfile_dss        = &$getid3->info['dss'];

		$getid3->info['fileformat']            = 'dss';
		$getid3->info['audio']['dataformat']   = 'dss';
		$getid3->info['audio']['bitrate_mode'] = 'cbr';
		//$thisfile_dss['encoding']              = 'ISO-8859-1';

		$thisfile_dss['date_create']    = $this->DSSdateStringToUnixDate(substr($DSSheader,  38,  12));
		$thisfile_dss['date_complete']  = $this->DSSdateStringToUnixDate(substr($DSSheader,  50,  12));
		//$thisfile_dss['length']         =                         intval(substr($DSSheader,  62,   6)); // I thought time was in seconds, it's actually HHMMSS
		$thisfile_dss['length']         = intval((substr($DSSheader,  62, 2) * 3600) + (substr($DSSheader,  64, 2) * 60) + substr($DSSheader,  66, 2));
		$thisfile_dss['priority']       =                            ord(substr($DSSheader, 793,   1));
		$thisfile_dss['comments']       =                           trim(substr($DSSheader, 798, 100));


		//$getid3->info['audio']['bits_per_sample']  = ?;
		//$getid3->info['audio']['sample_rate']      = ?;
		$getid3->info['audio']['channels']     = 1;

		$getid3->info['playtime_seconds'] = $thisfile_dss['length'];
		$getid3->info['audio']['bitrate'] = ($getid3->info['filesize'] * 8) / $getid3->info['playtime_seconds'];

		return true;
	}

	function DSSdateStringToUnixDate($datestring) {
		$y = substr($datestring,  0, 2);
		$m = substr($datestring,  2, 2);
		$d = substr($datestring,  4, 2);
		$h = substr($datestring,  6, 2);
		$i = substr($datestring,  8, 2);
		$s = substr($datestring, 10, 2);
		$y += (($y < 95) ? 2000 : 1900);
		return mktime($h, $i, $s, $m, $d, $y);
	}

}

?>