<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.aa.php                                         //
// module for analyzing Audible Audiobook files                //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_aa
{

	function getid3_aa(&$fd, &$ThisFileInfo) {

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$AAheader  = fread($fd, 8);

		$magic = "\x57\x90\x75\x36";
		if (substr($AAheader, 4, 4) != $magic) {
			$ThisFileInfo['error'][] = 'Expecting "'.PrintHexBytes($magic).'" at offset '.$ThisFileInfo['avdataoffset'].', found "'.PrintHexBytes(substr($AAheader, 4, 4)).'"';
			return false;
		}

		// shortcut
		$ThisFileInfo['aa'] = array();
		$thisfile_au        = &$ThisFileInfo['aa'];

		$ThisFileInfo['fileformat']            = 'aa';
		$ThisFileInfo['audio']['dataformat']   = 'aa';
		$ThisFileInfo['audio']['bitrate_mode'] = 'cbr'; // is it?
		$thisfile_au['encoding']               = 'ISO-8859-1';

		$thisfile_au['filesize'] = getid3_lib::BigEndian2Int(substr($AUheader,  0, 4));
		if ($thisfile_au['filesize'] > ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset'])) {
			$ThisFileInfo['warning'][] = 'Possible truncated file - expecting "'.$thisfile_au['filesize'].'" bytes of data, only found '.($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']).' bytes"';
		}

		$ThisFileInfo['audio']['bits_per_sample'] = 16; // is it?
		$ThisFileInfo['audio']['sample_rate']  = $thisfile_au['sample_rate'];
		$ThisFileInfo['audio']['channels']     = $thisfile_au['channels'];

		//$ThisFileInfo['playtime_seconds'] = 0;
		//$ThisFileInfo['audio']['bitrate'] = 0;

		return true;
	}

}


?>