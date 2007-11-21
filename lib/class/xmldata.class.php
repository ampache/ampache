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

	// This is added so that we don't pop any webservers
	public static $limit = '5000';

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
	 * single_string
	 * This takes two values, first the key second the string
	 */
	public static function single_string($key,$string) { 

		$final = self::_header() . "\t<$key><![CDATA[$string]]></$key>" . self::_footer(); 

		return $final; 

	} // single_string

	/**
	 * artists
	 * This takes an array of artists and then returns a pretty xml document with the information 
	 * we want 
	 */
	public static function artists($artists) { 

		if (count($artists) > self::$limit) { 
			$artists = array_splice($artists,0,self::$limit); 
		} 

		foreach ($artists as $artist_id) { 
			$artist = new Artist($artist_id); 
			$artist->format(); 

			$string .= "<artist id=\"$artist->id\">\n" . 
					"\t<name><![CDATA[$artist->f_full_name]]></name>\n" .  
					"</artist>\n"; 
		} // end foreach artists

		$final = self::_header() . $string . self::_footer(); 
		return $final; 

	} // artists

	/**
	 * albums
	 * This echos out a standard albums XML document, it pays attention to the limit
	 */
	public static function albums($albums) { 

		if (count($albums) > self::$limit) { 
			$albums = array_splice($albums,0,self::$limit); 
		} 

		foreach ($albums as $album_id) { 
			$album = new Album($album_id); 
			$album->format(); 

			// Build the Art URL, include session 
			$art_url = Config::get('web_path') . '/image.php?id=' . $album->id . '&auth=' . scrub_out($_REQUEST['auth']);  

			$string .= "<album id=\"$album->id\">\n" . 
					"\t<name><![CDATA[$album->name]]></name>\n"; 

			// Do a little check for artist stuff
			if ($album->artist_count != 1) { 
				$string .= "\t<artist id=\"0\"><![CDATA[Various]]></artist>\n"; 
			} 
			else { 
				$string .= "\t<artist id=\"$album->artist\"><![CDATA[$album->artist_name]]></artist>\n"; 
			} 

			$string .= "\t<year>$album->year</year>\n" . 
					"\t<tracks>$album->song_count</tracks>\n" . 
					"\t<disk>$album->disk</disk>\n" . 
					"\t<art><![CDATA[$art_url]]></art>\n" . 
					"</album>\n"; 
		} // end foreach

		$final = self::_header() . $string . self::_footer(); 

		return $final; 

	} // albums

	/**
	 * genres
	 * This takes an array of genre ids and returns a nice little pretty xml doc
	 */
	public static function genres($genres) { 

		if (count($genres) > self::$limit) { 
			$genres = array_slice($genres,0,self::$limit); 
		} 

		// Foreach the ids
		foreach ($genres as $genre_id) { 
			$genre = new Genre($genre_id); 
			$genre->format(); 
			$song_count 	= $genre->get_song_count(); 
			$album_count 	= $genre->get_album_count(); 
			$artist_count 	= $genre->get_artist_count(); 

			$string .= "<genre id=\"$genre->id\">\n" . 
					"\t<name><![CDATA[$genre->name]]></name>\n" . 
					"\t<songs>$song_count</songs>\n" . 
					"\t<albums>$album_count</albums>\n" . 
					"\t<artists>$artist_count</artists>\n" . 
					"</genre>\n"; 

		} // end foreach

		$final = self::_header() . $string . self::_footer(); 

		return $final; 

	} // genres

	/**
	 * songs
	 * This returns an xml document from an array of song ids spiffy isn't it!
	 */
	public static function songs($songs) { 

		if (count($songs) > self::$limit) { 
			$songs = array_slice($songs,0,self::$limit); 
		} 

		// Foreach the ids!
		foreach ($songs as $song_id) { 
			$song = new Song($song_id); 
			$song->format(); 

			$string .= "<song id=\"$song->id\">\n" . 
					"\t<title><![CDATA[$song->title]]></title>\n" . 
					"\t<artist id=\"$song->artist\"><![CDATA[$song->f_artist_full]]></artist>\n" . 
					"\t<album id=\"$song->album\"><![CDATA[$song->f_album_full]]></album>\n" . 
					"\t<genre id=\"$song->genre\"><![CDATA[$song->genre]]></genre>\n" . 
					"\t<track>$song->track</track>\n" . 
					"\t<time>$song->time</time>\n" . 
					"\t<url><![CDATA[" . $song->get_url($_REQUEST['auth']) . "]]></url>\n" . 
					"</song>\n"; 

		} // end foreach

		$final = self::_header() . $string . self::_footer(); 

		return $final;

	} // songs

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
