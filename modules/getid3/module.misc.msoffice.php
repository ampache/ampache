<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.archive.doc.php                                      //
// module for analyzing MS Office (.doc, .xls, etc) files      //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_msoffice
{

	function getid3_msoffice(&$fd, &$ThisFileInfo) {
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);
		$DOCFILEheader = fread($fd, 8);
		$magic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
		if (substr($DOCFILEheader, 0, 8) != $magic) {
			$ThisFileInfo['error'][] = 'Expecting "'.getid3_lib::PrintHexBytes($magic).'" at '.$ThisFileInfo['avdataoffset'].', found '.getid3_lib::PrintHexBytes(substr($DOCFILEheader, 0, 8)).' instead.';
			return false;
		}
		$ThisFileInfo['fileformat'] = 'msoffice';

$ThisFileInfo['error'][] = 'MS Office (.doc, .xls, etc) parsing not enabled in this version of getID3() [v'.GETID3_VERSION.']';
return false;

	}

}


?>