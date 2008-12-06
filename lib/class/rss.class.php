<?php
/*

 Copyright Ampache.org
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
 * RSS Class
 * This is not currently used by the stable version of ampache, really here for future use and
 * due to the fact it was back-ported from /trunk
 */
class RSS {

	public $type; 
	public $data; 

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	public function __construct($type) { 	

		if (!RSS::valid_type($type)) { 
			$type = 'now_playing'; 
		} 			

	} // constructor

	/**
	 * validate_type
	 * this returns a valid type for an rss feed, if the specified type is invalid it returns a default value
	 */
	public static function validate_type($type) { 

		$valid_types = array('now_playing','recently_played','latest_album','latest_artist','latest_song',
				'popular_song','popular_album','popular_artist'); 
		
		if (!in_array($type,$valid_types)) { 
			return 'now_playing'; 
		} 

		return $type; 

	} // validate_type

	/**
 	 * get_display
	 * This dumps out some html and an icon for the type of rss that we specify
	 */
	public static function get_display($type='nowplaying') { 

		// Default to now playing
		if (!in_array($type,self::$types)) { 
			$type = 'nowplaying'; 
		} 	

		$string = '<a href="' . Config::get('web_path') . '/rss.php?type=' . $type . '">' . get_user_icon('feed',_('RSS Feed')) . '</a>';  

		return $string; 

	} // get_display

} // end RSS class
