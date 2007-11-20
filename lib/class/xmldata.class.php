<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * xmlData
 * This class takes care of all of the xml document stuff in Ampache these
 * are all static calls 
 */
class xmlData { 

	public static $version = '340001'; 

	/**
	 * constructor
	 * We don't use this, as its really a static class
	 */
	private function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * error
	 * This generates a standard XML Error message
	 * nothing fancy here...
	 */
	public static function error($string) { 

		$string = self::_header() . "\t<error><![CDATA[$string]]></error>" . self::_footer(); 
		return $string; 

	} // error

	/**
	 * artists
	 * This takes an array of artists and then returns a pretty xml document with the information 
	 * we want 
	 */
	public static function artists($artists) { 

		foreach ($artists as $artist_id) { 
			$artist = new Artist($artist_id); 
			$artist->format(); 

			$string .= "<artist id="$artist->id">\n" . 
					"\t<name>$artist->f_full_name</name>\n"; 
					"</artist>\n"; 
		} // end foreach artists

		$final = self::_header() . $string . self::_footer(); 
		return $final; 

	} // artists

	/**
	 * _header
	 * this returns a standard header, there are a few types
	 * so we allow them to pass a type if they want to
	 */
	private static function _header($type='') { 

		switch ($type) { 
			case 'xspf': 

			break; 
			case 'itunes':
			
			break; 
			default: 
				$header = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<root>\n";
			break; 
		} // end switch 

		return $header; 

	} // _header

	/**
 	 * _footer
 	 * this returns the footer for this document, these are pretty boring
	 */
	private static function _footer($type='') { 

		switch ($type) { 
			default: 
				$footer = "\n</root>\n"; 
			break; 
		} // end switch on type 


		return $footer; 

	} // _footer

} // xmlData

?>
