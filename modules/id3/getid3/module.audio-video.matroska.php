<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.matriska.php                             //
// module for analyzing Matroska containers                    //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_matroska
{

	function getid3_matroska(&$fd, &$ThisFileInfo) {

		$ThisFileInfo['fileformat'] = 'matroska';

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		//$ThisFileInfo['matroska']['raw']['a'] = $this->EBML2Int(fread($fd, 4));

		$ThisFileInfo['error'][] = 'Mastroka parsing not enabled in this version of getID3()';
		return false;

	}


	function EBML2Int($EBMLstring) {
		// http://matroska.org/specs/

		// Element ID coded with an UTF-8 like system:
		// 1xxx xxxx                                  - Class A IDs (2^7 -2 possible values) (base 0x8X)
		// 01xx xxxx  xxxx xxxx                       - Class B IDs (2^14-2 possible values) (base 0x4X 0xXX)
		// 001x xxxx  xxxx xxxx  xxxx xxxx            - Class C IDs (2^21-2 possible values) (base 0x2X 0xXX 0xXX)
		// 0001 xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx - Class D IDs (2^28-2 possible values) (base 0x1X 0xXX 0xXX 0xXX)
		// Values with all x at 0 and 1 are reserved (hence the -2).

		// Data size, in octets, is also coded with an UTF-8 like system :
		// 1xxx xxxx                                                                              - value 0 to  2^7-2
		// 01xx xxxx  xxxx xxxx                                                                   - value 0 to 2^14-2
		// 001x xxxx  xxxx xxxx  xxxx xxxx                                                        - value 0 to 2^21-2
		// 0001 xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                                             - value 0 to 2^28-2
		// 0000 1xxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                                  - value 0 to 2^35-2
		// 0000 01xx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx                       - value 0 to 2^42-2
		// 0000 001x  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx            - value 0 to 2^49-2
		// 0000 0001  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx  xxxx xxxx - value 0 to 2^56-2

		if (0x80 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x7F);
		} elseif (0x40 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x3F);
		} elseif (0x20 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x1F);
		} elseif (0x10 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x0F);
		} elseif (0x08 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x07);
		} elseif (0x04 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x03);
		} elseif (0x02 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x01);
		} elseif (0x01 & ord($EBMLstring{0})) {
			$EBMLstring{0} = chr(ord($EBMLstring{0}) & 0x00);
		} else {
			return false;
		}
		return getid3_lib::BigEndian2Int($EBMLstring);
	}

}

?>