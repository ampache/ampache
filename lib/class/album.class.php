<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/**
 * Album Class
 * This is the class responsible for handling the Album object
 * it is related to the album table in the database.
 */
class Album extends database_object {

	/* Variables from DB */
	public $id;
	public $name;
	public $full_name; // Prefix + Name, genereated by format();
	public $disk;
	public $year;
	public $prefix;
	public $mbid; // MusicBrainz ID

	/* Art Related Fields */
	public $art;
	public $art_mime;
	public $thumb;
	public $thumb_mime;

	// cached information
	public $_songs=array();

	/**
	 * __construct
	 * Album constructor it loads everything relating
	 * to this album from the database it does not
	 * pull the album or thumb art by default or
	 * get any of the counts.
	 */
	public function __construct($id='') {

		if (!$id) { return false; }

		/* Get the information from the db */
		$info = $this->get_info($id);

		// Foreach what we've got
		foreach ($info as $key=>$value) {
			$this->$key = $value;
		}

		// Little bit of formating here
		$this->full_name = trim($info['prefix'] . ' ' . $info['name']);

		return true;

	} // constructor

	/**
	 * construct_from_array
	 * This is often used by the metadata class, it fills out an album object from a
	 * named array, _fake is set to true
	 */
	public static function construct_from_array($data) {

		$album = new Album(0);
		foreach ($data as $key=>$value) {
			$album->$key = $value;
		}

		// Make sure that we tell em it's fake
		$album->_fake = true;

		return $album;

	} // construct_from_array

	/**
	 * build_cache
	 * This takes an array of object ids and caches all of their information
	 * with a single query
	 */
	public static function build_cache($ids,$extra=false) {

		// Nothing to do if they pass us nothing
		if (!is_array($ids) OR !count($ids)) { return false; }

		$idlist = '(' . implode(',', $ids) . ')';

		$sql = "SELECT * FROM `album` WHERE `id` IN $idlist";
		$db_results = Dba::read($sql);

		while ($row = Dba::fetch_assoc($db_results)) {
			parent::add_to_cache('album',$row['id'],$row);
		}

		// If we're extra'ing cache the extra info as well
		if ($extra) {
			$sql = "SELECT COUNT(DISTINCT(song.artist)) as artist_count,COUNT(song.id) AS song_count,artist.name AS artist_name" .
				",artist.prefix AS artist_prefix,album_data.art AS has_art,album_data.thumb AS has_thumb, artist.id AS artist_id,`song`.`album`".
				"FROM `song` " .
				"INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
				"LEFT JOIN `album_data` ON `album_data`.`album_id` = `song`.`album` " .
				"WHERE `song`.`album` IN $idlist GROUP BY `song`.`album`";

			$db_results = Dba::read($sql);

			while ($row = Dba::fetch_assoc($db_results)) {
				$row['has_art'] = make_bool($row['has_art']);
				$row['has_thumb'] = make_bool($row['has_thumb']);
				parent::add_to_cache('album_extra',$row['album'],$row);
			} // while rows
		} // if extra

		return true;

	} // build_cache

	/**
	 * _get_extra_info
	 * This pulls the extra information from our tables, this is a 3 table join, which is why we don't normally
	 * do it
	 */
	private function _get_extra_info() {

		if (parent::is_cached('album_extra',$this->id)) {
			return parent::get_from_cache('album_extra',$this->id);
		}

		$sql = "SELECT COUNT(DISTINCT(song.artist)) as artist_count,COUNT(song.id) AS song_count,artist.name AS artist_name" .
			",artist.prefix AS artist_prefix,album_data.art AS has_art,album_data.thumb AS has_thumb, artist.id AS artist_id ".
			"FROM `song` " .
			"INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` " .
			"LEFT JOIN `album_data` ON `album_data`.`album_id` = `song`.`album` " .
			"WHERE `song`.`album`='$this->id' GROUP BY `song`.`album`";
		$db_results = Dba::read($sql);

		$results = Dba::fetch_assoc($db_results);

		if ($results['has_art']) { $results['has_art'] = 1; }
		if ($results['has_thumb']) { $results['has_thumb'] = 1; }

		parent::add_to_cache('album_extra',$this->id,$results);

		return $results;

	} // _get_extra_info

	/**
	 * get_songs
	 * gets the songs for this album takes an optional limit
	 * and an optional artist, if artist is passed it only gets
	 * songs with this album + specified artist
	 */
	public function get_songs($limit = 0,$artist='') {

		$results = array();

		if ($artist) {
			$artist_sql = "AND `artist`='" . Dba::escape($artist) . "'";
		}

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' $artist_sql ORDER BY `track`, `title`";
		if ($limit) { $sql .= " LIMIT $limit"; }
		$db_results = Dba::read($sql);

		while ($r = Dba::fetch_assoc($db_results)) {
			$results[] = $r['id'];
		}

		return $results;

	} // get_songs

	/**
	 * has_art
	 * This returns true or false depending on if we find any art for this
	 * album.
	 */
	public function has_art() {

		$sql = "SELECT `album_id` FROM `album_data` WHERE `album_id`='" . $this->id . "' AND art IS NOT NULL";
		$db_results = Dba::read($sql);

		if (Dba::fetch_assoc($db_results)) {
			$this->has_art = true;
			return true;
		}

		return false;

	} // has_art

	/**
	 * has_track
	 * This checks to see if this album has a track of the specified title
	 */
	public function has_track($title) {

		$title = Dba::escape($title);

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' AND `title`='$title'";
		$db_results = Dba::read($sql);

		$data = Dba::fetch_assoc($db_results);

		return $data;

	} // has_track

	/**
	 * format
	 * This is the format function for this object. It sets cleaned up
	 * album information with the base required
	 * f_link, f_name
	 */
	public function format() {

		$web_path = Config::get('web_path');

		/* Pull the advanced information */
		$data = $this->_get_extra_info();
		foreach ($data as $key=>$value) { $this->$key = $value; }

		/* Truncate the string if it's to long */
	  	$this->f_name		= truncate_with_ellipsis($this->full_name,Config::get('ellipse_threshold_album'));

		$this->f_name_link	= "<a href=\"$web_path/albums.php?action=show&amp;album=" . scrub_out($this->id) . "\" title=\"" . scrub_out($this->full_name) . "\">" . $this->f_name;
		// If we've got a disk append it
		if ($this->disk) {
			$this->f_name_link .= " <span class=\"discnb disc" .$this->disk. "\">[" . _('Disk') . " " . $this->disk . "]</span>";
		}
		$this->f_name_link .="</a>";

		$this->f_link 		= $this->f_name_link;
		$this->f_title		= $full_name;
		if ($this->artist_count == '1') {
			$artist = scrub_out(truncate_with_ellipsis(trim($this->artist_prefix . ' ' . $this->artist_name),Config::get('ellipse_threshold_artist')));
			$this->f_artist_link = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $this->artist_id . "\" title=\"" . scrub_out($this->artist_name) . "\">" . $artist . "</a>";
			$this->f_artist = $artist;
		}
		else {
			$this->f_artist_link = "<span title=\"$this->artist_count " . _('Artists') . "\">" . _('Various') . "</span>";
			$this->f_artist = _('Various');
		}

		if ($this->year == '0') {
			$this->year = "N/A";
		}

		$tags = Tag::get_top_tags('album',$this->id);
		$this->tags = $tags;

		$this->f_tags = Tag::get_display($tags,$this->id,'album');


		// Format the artist name to include the prefix
		$this->f_artist_name = trim($this->artist_prefix . ' ' . $this->artist_name);

	} // format

	/**
	 * get_random_songs
	 * gets a random number, and a random assortment of songs from this album
	 */
	function get_random_songs() {

		$sql = "SELECT `id` FROM `song` WHERE `album`='$this->id' ORDER BY RAND()";
		$db_results = Dba::read($sql);

		while ($r = Dba::fetch_row($db_results)) {
			$results[] = $r['0'];
		}

		return $results;

	} // get_random_songs

	/**
	 * update
	 * This function takes a key'd array of data and updates this object
	 * as needed, and then throws down with a flag
	 */
	public function update($data) {

		$year 		= $data['year'];
		$artist		= $data['artist'];
		$name		= $data['name'];
		$disk		= $data['disk'];
		$mbid		= $data['mbid'];

		$current_id = $this->id;

		if ($artist != $this->artist_id AND $artist) {
			// Update every song
			$songs = $this->get_songs();
			foreach ($songs as $song_id) {
				Song::update_artist($artist,$song_id);
			}
			$updated = 1;
			Catalog::clean_artists();
		}

		$album_id = Catalog::check_album($name,$year,$disk,$mbid);
		if ($album_id != $this->id) {
			if (!is_array($songs)) { $songs = $this->get_songs(); }
			foreach ($songs as $song_id) {
				Song::update_album($album_id,$song_id);
				Song::update_year($year,$song_id);
			}
			$current_id = $album_id;
			$updated = 1;
			Catalog::clean_albums();
		}

		if ($updated) {
			// Flag all songs
			foreach ($songs as $song_id) {
				Flag::add($song_id,'song','retag','Interface Album Update');
				Song::update_utime($song_id);
			} // foreach song of album
			Catalog::clean_stats();
		} // if updated


		return $current_id;

	} // update

	/**
	 * clear_art
	 * clears the album art from the DB
	 */
	public function clear_art() {

		$sql = "UPDATE `album_data` SET `art`=NULL, `art_mime`=NULL, `thumb`=NULL, `thumb_mime`=NULL WHERE `album_id`='$this->id'";
		$db_results = Dba::write($sql);

	} // clear_art

	/**
	 * insert_art
	 * this takes a string representation of an image
	 * and inserts it into the database. You must pass the mime type as well
	 */
	public function insert_art($image, $mime) {

		/* Have to disable this for Demo because people suck and try to
 		 * insert PORN :(
		 */
		if (Config::get('demo_mode')) { return false; }

		// Check for PHP:GD and if we have it make sure this image is of some size
		if (function_exists('ImageCreateFromString')) {
			$im = ImageCreateFromString($image);
			if (imagesx($im) <= 5 || imagesy($im) <= 5 || !$im) {
				return false;
			}
		} // if we have PHP:GD
		elseif (strlen($image) < 5) {
			return false;
		}

		// Default to image/jpeg as a guess if there is no passed mime type
		$mime = $mime ? $mime : 'image/jpeg';

		// Push the image into the database
		$sql = "REPLACE INTO `album_data` SET `art` = '" . Dba::escape($image) . "'," .
			" `art_mime` = '" . Dba::escape($mime) . "'" .
			", `album_id` = '$this->id'," .
			"`thumb` = NULL, `thumb_mime`=NULL";
		$db_results = Dba::write($sql);

		return true;

	} // insert_art

	/**
	 * save_resized_art
	 * This takes data from a gd resize operation and saves
	 * it back into the database as a thumbnail
	 */
	public static function save_resized_art($data,$mime,$album) {

		// Make sure there's actually something to save
		if (strlen($data) < '5') { return false; }

		$data = Dba::escape($data);
		$mime = Dba::escape($mime);
		$album = Dba::escape($album);

		$sql = "UPDATE `album_data` SET `thumb`='$data',`thumb_mime`='$mime' " .
			"WHERE `album_data`.`album_id`='$album'";
		$db_results = Dba::write($sql);

	} // save_resized_art

	/**
	 * get_random_albums
	 * This returns a random number of albums from the catalogs
	 * this is used by the index to return some 'potential' albums to play
	 */
	public static function get_random_albums($count=6) {

		$sql = 'SELECT `id` FROM `album` ORDER BY RAND() LIMIT ' . ($count*2);
		$db_results = Dba::read($sql);

		$in_sql = '`album_id` IN (';

		while ($row = Dba::fetch_assoc($db_results)) {
			$in_sql .= "'" . $row['id'] . "',";
			$total++;
		}

		if ($total < $count) { return false; }

		$in_sql = rtrim($in_sql,',') . ')';

		$sql = "SELECT `album_id`,ISNULL(`art`) AS `no_art` FROM `album_data` WHERE $in_sql";
		$db_results = Dba::read($sql);
		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$results[$row['album_id']] = $row['no_art'];
		} // end for

		asort($results);
		$albums = array_keys($results);
		$results = array_slice($albums,0,$count);

		return $results;

	} // get_random_albums

	/**
	 * get_image_from_source
	 * This gets an image for the album art from a source as
	 * defined in the passed array. Because we don't know where
	 * its comming from we are a passed an array that can look like
	 * ['url'] 	= URL *** OPTIONAL ***
	 * ['file']	= FILENAME *** OPTIONAL ***
	 * ['raw'] 	= Actual Image data, already captured
	 */
	public static function get_image_from_source($data) {

		// Already have the data, this often comes from id3tags
		if (isset($data['raw'])) {
			return $data['raw'];
		}

		// If it came from the database
		if (isset($data['db'])) {
			// Repull it
			$album_id = Dba::escape($data['db']);
			$sql = "SELECT * FROM `album_data` WHERE `album_id`='$album_id'";
			$db_results = Dba::read($sql);
			$row = Dba::fetch_assoc($db_results);
			return $row['art'];
		} // came from the db

		// Check to see if it's a URL
		if (isset($data['url'])) {
			$snoopy = new Snoopy();
					if(Config::get('proxy_host') AND Config::get('proxy_port')) {
						$snoopy->proxy_user = Config::get('proxy_host');
						$snoopy->proxy_port = Config::get('proxy_port');
						$snoopy->proxy_user = Config::get('proxy_user');
						$snoopy->proxy_pass = Config::get('proxy_pass');
					}
			$snoopy->fetch($data['url']);
			return $snoopy->results;
		}

		// Check to see if it's a FILE
		if (isset($data['file'])) {
			$handle = fopen($data['file'],'rb');
			$image_data = fread($handle,filesize($data['file']));
			fclose($handle);
			return $image_data;
		}

		// Check to see if it is embedded in id3 of a song
		if (isset($data['song'])) {
			// If we find a good one, stop looking
			$getID3 = new getID3();
			$id3 = $getID3->analyze($data['song']);

			if ($id3['format_name'] == "WMA") {
				return $id3['asf']['extended_content_description_object']['content_descriptors']['13']['data'];
			}
			elseif (isset($id3['id3v2']['APIC'])) {
				// Foreach incase they have more then one
				foreach ($id3['id3v2']['APIC'] as $image) {
					return $image['data'];
				}
			}
		} // if data song

		return false;

	} // get_image_from_source

	/**
	 * get_art_url
	 * This returns the art URL for the album
	 */
	public static function get_art_url($album_id,$sid=false) {

		$sid = $sid ? scrub_out($sid) : session_id();

		$sql = "SELECT `art_mime`,`thumb_mime` FROM `album_data` WHERE `album_id`='" . Dba::escape($album_id) . "'";
		$db_results = Dba::read($sql);

		$row = Dba::fetch_assoc($db_results);

		$mime = $row['thumb_mime'] ? $row['thumb_mime'] : $row['art_mime'];

		switch ($type) {
			case 'image/gif':
				$type = 'gif';
			break;
			case 'image/png':
				$type = 'png';
			break;
			default:
			case 'image/jpeg':
				$type = 'jpg';
			break;
		} // end type translation

		$name = 'art.' . $type;

		$url = Config::get('web_path') . '/image.php?id=' . scrub_out($album_id) . '&auth=' . $sid . '&name=' . $name;

		return $url;

	} // get_art_url

} //end of album class

?>
