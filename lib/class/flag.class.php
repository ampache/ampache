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
 * Flag Class
 * This handles flagging of songs, albums and artists	
 */
class Flag {

	public $id; 
	public $user;
	public $object_id;
	public $object_type;
	public $comment;
	public $flag;
	public $date;
	public $approved=0;

	/* Generated Values */
	public $name; // Blank
	public $title; // Blank

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	public function __construct($flag_id) { 

		$info = $this->_get_info($flag_id);
		
		foreach ($info as $key=>$value) { 
			$this->$key = $value; 
		} 

		return true;

	} // Constructor

	/**
	 * _get_info
	 * Private function for getting the information for this object from the database 
	 */
	private function _get_info($flag_id) { 

		$id = Dba::escape($flag_id);

		$sql = "SELECT * FROM `flagged` WHERE `id`='$id'";
		$db_results = Dba::query($sql);

		$results = Dba::fetch_assoc($db_results);
		
		return $results;

	} // _get_info

	/**
	 * get_recent
	 * This returns the id's of the most recently flagged songs, it takes an int
	 * as an argument which is the count of the object you want to return
	 */
	public static function get_recent($count=0) { 

		if ($count) { $limit = " LIMIT " . intval($count);  } 

		$results = array();

		$sql = "SELECT id FROM flagged ORDER BY date " . $limit;
		$db_results = Dba::query($sql);

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}
		
		return $results;

	} // get_recent

	/**
	 * get_total
	 * Returns the total number of flagged objects
	 */
	function get_total() { 

		$sql = "SELECT COUNT(id) FROM flagged";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_row($db_results);

		return $results['0'];

	} // get_total

	/**
	 * get_all
	 * This returns an array of ids of flagged songs if no limit is passed
	 * it gets everything
	 */
	public static function get_all($count=0) { 

		if ($count) { $limit_clause = "LIMIT " . intval($count); } 
		
		$sql = "SELECT `id` FROM `flagged` $limit_clause";
		$db_results = Dba::query($sql);

		/* Default it to an array */
		$results = array();

		/* While the query */
		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row['id'];
		}

		return $results;

	} // get_all

	/**
	 * get_approved
	 * This returns an array of approved flagged songs
	 */
	public static function get_approved() { 

		$sql = "SELECT `id` FROM `flagged` WHERE `approved`='1'";
		$db_results = Dba::query($sql);  


		/* Default the results array */
		$results = array(); 

		/* While it */
		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_approved

	/**
	 * add
	 * This adds a flag entry for an item, it takes an id, a type, the flag type
	 * and a comment and then inserts the mofo
	 */
	public static function add($id,$type,$flag,$comment) { 
	
		$id 		= Dba::escape($id);
		$type		= Dba::escape($type);
		$flag		= self::validate_flag($flag);
		$user		= Dba::escape($GLOBALS['user']->id);
		$comment	= Dba::escape($comment);
		$time		= time();
		$approved	= '0';

		/* If they are an content manager or higher, it's auto approved */
		if (Access::check('interface','75')) { $approved = '1'; } 

		$sql = "INSERT INTO `flagged` (`object_id`,`object_type`,`flag`,`comment`,`date`,`approved`,`user`) VALUES " . 
			" ('$id','$type','$flag','$comment','$time','$approved','$user')";
		$db_results = Dba::query($sql);

		return true;

	} // add

	/**
	 * delete
	 * This deletes the flagged entry and rescans the file to revert to the origional
	 * state, in a perfect world, I could just roll the changes back... not until 3.4
	 * or.. haha 3.5!
	 */
	public function delete() { 

		// Re-scan the file
		$song = new Song($this->object_id); 
		$info = Catalog::update_song_from_tags($song); 

		// Delete the row
		$sql = "DELETE FROM `flagged` WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

		// Reset the Last-Updated date so that it'll get re-scaned 	
		$song->update_utime($song->id,1); 

		return true;

	} // delete

	/**
	 * approve
	 * This approves the current flag object ($this->id) by setting approved to
	 * 1
	 */
	 public function approve() { 

		$sql = "UPDATE `flagged` SET `approved`='1' WHERE `id`='$this->id'";
		$db_results = Dba::query($sql);

		$this->approved = 1; 

		return true;
	
	} // approve
	
	/**
	 * format
	 * This function figures out what kind of object we've got and sets up all the
	 * vars all nice and fuzzy like
	 */
	public function format() { 

		switch ($this->object_type) { 
			case 'song': 
				$song = new Song($this->object_id);
				$song->format(); 
				$this->f_name 	= $song->f_link;
			break;
		} // end switch on type 

		$client = new User($this->user);
		$client->format(); 
		$this->f_user = $client->f_link; 

	} // format
 
	/**
	 * print_status
	 * This prints out a userfriendly version of the current status for this flagged
	 * object
	 */
	public function print_status() { 

		if ($this->approved) { echo _('Approved'); }
		else { echo _('Pending'); }

	} // print_status

	/**
	 * print_flag
	 * This prints out a userfriendly version of the current flag type
	 */
	public function print_flag() { 

		switch ($this->flag) { 
			case 'delete':
				$name = _('Delete');
			break;
			case 'retag':
				$name = _('Re-Tag'); 
			break;
			case 'reencode':
				$name = _('Re-encode');
			break;
			case 'other':
				$name = _('Other'); 
			break;
			default:
				$name = _('Unknown');
			break;
		} // end switch

		echo $name;
		
	} // print_flag

	/**
	 * validate_flag
	 * This takes a flag input and makes sure it's one of the reigstered
	 * and valid 'flag' values
	 */
	public static function validate_flag($flag) { 

		switch ($flag) { 
			case 'delete': 
			case 'retag': 
			case 'reencode': 
			case 'other': 
				return $flag; 
			break;
			default: 
				return 'other'; 
			break;
		} // end switch

	} // validate_flag

	/**
	 * fill_tags
	 * This is used by the write_tags script. 
	 */
	public static function fill_tags( $tagWriter, $song, $type = 'comment' ) {

	        // Set all of the attributes for the tag to be written(All pulled from the song object)
	        // Use a function since ID3v1, ID3v2, and vorbis/flac/ape are different
		switch ($type) { 
			case 'comment': 
		                $tagWriter->comments['title'] = $song->title;
		                $tagWriter->comments['date'] = $song->year;
		                $tagWriter->comments['year'] = $song->year;
		                $tagWriter->comments['comment'] = $song->comment;
		                $tagWriter->comments['size'] = $song->size;
		                $tagWriter->comments['time'] = $song->time;
		                $tagWriter->comments['album'] = $song->get_album_name();
		                $tagWriter->comments['artist'] = $song->get_artist_name();
		                $tagWriter->comments['genre'] = $song->get_genre_name();
		                $tagWriter->comments['track'] = $song->track;
			break; 
			case 'id3v1':
	                	$tagWriter->title = $song->title;
		                $tagWriter->year = $song->year;
		                $tagWriter->comment = $song->comment;
		                $tagWriter->artist = $song->get_artist_name();
		                $tagWriter->album = $song->get_album_name();
		                $tagWriter->genre = $song->get_genre_name();
		                $tagWriter->track = $song->track;
		                unset($tagWriter->genre_id);
			break;
			case 'id3v2':
	                	$tagWriter->title = $song->title;
		                $tagWriter->year = $song->year;
		                $tagWriter->comment = $song->comment;
		                $tagWriter->artist = $song->get_artist_name();
		                $tagWriter->album = $song->get_album_name();
		                $tagWriter->genre = $song->get_genre_name();
		                $tagWriter->track = $song->track;
		                unset($tagWriter->genre_id);
			break;
	        } // end switch on type

	} // fill_tags


} //end of flag class

?>
