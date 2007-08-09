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
					$data['links']		= "<a target=\"_blank\" href=\"" . $item['URI'] . "\">" . get_user_icon('world_link','MyStrands Link') . "</a>"; 

					// Do a search for this artist in our instance
					$artist_id = Catalog::check_artist($data['name'],1); 
					if ($artist_id) { 
						$artist = new Artist($artist_id); 
						$artist->format(); 
						$data['links'] 	.= "<a href=\"$artist->f_link\">" . get_user_icon('ampache','Ampache') . "</a>"; 
					} 

					$objects[] = Artist::construct_from_array($data); 
				break;
			} // end switch on type
		} // end foreach 

		return $objects; 

	} // recommend_similar

	/**
	 * find_missing_tracks
	 * This returns an array of song objects using the construct_from_array() that are
	 * not in the specified album. 
	 */
	public static function find_missing_tracks($album_id) { 

		// Build our object
		$album = new Album($album_id); 
		$album->format(); 
		$objects = array(); 

                // For now it's only mystrands
                OpenStrands::set_auth_token(Config::get('mystrands_developer_key'));
                $openstrands = new OpenStrands($GLOBALS['user']->prefs['mystrands_user'],$GLOBALS['user']->prefs['mystrands_pass']);

		if (!$openstrands) { return false; } 

		// Setup the string we're going to pass
		if ($album->artist_count == '1') { $artist_name = $album->artist_name; } 
		else { $artist_name = "Various"; } 

		$data[] = array('artist'=>$artist_name,'album'=>$album->full_name); 

		// First find the album on mystrands
		$result = $openstrands->match_albums($data); 
		
		if (!$result) { return false; } 

		$mystrands_id = $result['0']['__attributes']['AlbumId']; 

		if (!$mystrands_id) { return false; } 

		$tracks = $openstrands->lookup_album_tracks($mystrands_id,Openstrands::$alias); 		

		// Recurse the data we've found and check the local album
		foreach ($tracks as $track) { 
			if (!$album->has_track($track['TrackName'])) { 
				$data['title'] 	= $track['TrackName']; 
				$data['track']	= $track['TrackNumber']; 
				$data['disc']	= $track['DiscNumber']; 
				$data['artist']	= $track['ArtistName']; 
				$data['links']	= "<a target=\"_blank\" href=\"" . $track['URI'] . "\">" . get_user_icon('world_link','MyStrands') . "</a>"; 
				// If we've got a purchase URL
				if ($track['UserPurchaseURI']) { 
					$data['links'] .= "<a target=\"_blank\" href=\"" . $track['UserPurchaseURI'] . "\">" . get_user_icon('money',_('Buy Track from MyStrands')) . "</a>"; 
				} 
				$objects[] = Album::construct_from_array($data); 
			}
		} // end foreach

		return $objects; 

	} // find_missing_tracks

} // metadata

?>
