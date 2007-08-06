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
 * metadata class
 * This class is a abstraction layer for getting
 * meta data for any object in Ampache, this includes
 * album art, lyrics, id3tags, recommendations etc 
 * it makes use of Object::construct_from_array() as needed
 */
class metadata { 

	/**
	 * constructor
	 * We don't use this, as its really a static class
	 */
	private function __construct() { 

		// Rien a faire

	} // constructor

	/**
	 * recommend_similar
	 * This takes the input and returns an array of objects construct_from_array()'d
	 */
	public static function recommend_similar($type,$id,$limit='') { 

		// For now it's only mystrands
		OpenStrands::set_auth_token(Config::get('mystrands_developer_key')); 
		$openstrands = new OpenStrands($GLOBALS['user']->prefs['mystrands_user'],$GLOBALS['user']->prefs['mystrands_pass']); 

		// Make sure auth worked
		if (!$openstrands) { return false; } 

		switch ($type) { 
			case 'artist': 
				$artist = new Artist($id); 
				$seed = array('name'=>array($artist->name)); 
				$results = $openstrands->recommend_artists($seed,$limit); 
			break;
		} 

		foreach ($results as $item) { 
			switch ($type) { 
				case 'artist': 
					$data['name']		= $item['ArtistName']; 
					$data['uid']		= $item['__attributes']['ArtistID']; 
					$data['mystrands_url']	= $item['URI']; 
					$data['links']		= "<a target=\"_blank\" href=\"" . $item['URI'] . "\">" . get_user_icon('mystrands','MyStrands Link') . "</a>"; 

					// Do a search for this artist in our instance
					$artist_id = Catalog::check_artist($data['name'],1); 
					if ($artist_id) { 
						$artist = new Artist($artist_id); 
						$artist->format(); 
						$data['links'] 	.= "<a href=\"$artist->f_link\">" . get_user_icon('link','Ampache') . "</a>"; 
					} 

					$objects[] = Artist::construct_from_array($data); 
				break;
			} // end switch on type
		} // end foreach 

		return $objects; 

	} // recommend_similar

} // metadata

?>
