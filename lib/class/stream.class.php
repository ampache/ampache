<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */


class Stream {

	public static $session;
	private static $session_inserted;

	private function __construct() {
		// Static class, do nothing.
	} 

	/**
	 * get_session
	 * This returns the current stream session
	 */
	public static function get_session() {

		if (!self::$session_inserted) {
			self::insert_session(self::$session);
		}

		return self::$session;

	} // get_session

	/**
	 * set_session
	 * This overrides the normal session value, without adding
	 * an additional session into the database, should be called
	 * with care
	 */
	public static function set_session($sid) {

		self::$session_inserted = true;
		self::$session=$sid;

	} // set_session

	/**
	 * insert_session
	 * This inserts a row into the session_stream table
	 */
	public static function insert_session($sid='',$uid='') {

		$sid = $sid ? Dba::escape($sid) : Dba::escape(self::$session);
		$uid = $uid ? Dba::escape($uid) : Dba::escape($GLOBALS['user']->id);

		$expire = time() + Config::get('stream_length');

		$sql = "INSERT INTO `session_stream` (`id`,`expire`,`user`) " .
			"VALUES('$sid','$expire','$uid')";
		$db_results = Dba::write($sql);

		if (!$db_results) { return false; }

		self::$session_inserted = true;

		return true;

	} // insert_session

	/**
	 * session_exists
	 * This checks to see if the passed stream session exists and is valid
	 */
	public static function session_exists($sid) {

		$sid 	= Dba::escape($sid);
		$time	= time();

		$sql = "SELECT * FROM `session_stream` WHERE `id`='$sid' AND `expire` > '$time'";
		$db_results = Dba::write($sql);

		if ($row = Dba::fetch_assoc($db_results)) {
			return true;
		}

		return false;

	} // session_exists

	/**
	 * gc_session
	 * This function performes the garbage collection stuff, run on extend
	 * and on now playing refresh.
	 */
	public static function gc_session() {

		$time = time();
		$sql = "DELETE FROM `session_stream` WHERE `expire` < '$time'";
		$db_results = Dba::write($sql);

		Stream_Playlist::clean();

	} // gc_session

	/**
	 * extend_session
	 * This takes the passed sid and does a replace into also setting the user
	 * agent and IP also do a little GC in this function
	 */
	public static function extend_session($sid,$uid) {

		$expire = time() + Config::get('stream_length');
		$sid 	= Dba::escape($sid);
		$agent	= Dba::escape($_SERVER['HTTP_USER_AGENT']);
		$ip	= Dba::escape(inet_pton($_SERVER['REMOTE_ADDR']));
		$uid	= Dba::escape($uid);

		$sql = "UPDATE `session_stream` SET `expire`='$expire', `agent`='$agent', `ip`='$ip' " .
			"WHERE `id`='$sid'";
		$db_results = Dba::write($sql);

		self::gc_session();

		return true;

	} // extend_session

	/**
	 * start_transcode
	 *
	 * This is a rather complex function that starts the transcoding or
	 * resampling of a song and returns the opened file handle. A reference
	 * to the song object is passed so that the changes we make in here
	 * affect the external object, References++
	 */
	public static function start_transcode(&$song, $song_name = 0, $start = 0) {

		// Check to see if bitrates are set.
		// If so let's go ahead and optimize!
		$max_bitrate = Config::get('max_bit_rate');
		$min_bitrate = Config::get('min_bit_rate');
		$time = time();
		$user_sample_rate = Config::get('sample_rate');

		if (!$song_name) {
			$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
		}

		if ($max_bitrate > 1 AND $min_bitrate < $max_bitrate AND $min_bitrate > 0) {
			$last_seen_time = $time - 1200; //20 min.

			$sql = "SELECT COUNT(*) FROM now_playing, user_preference, preference " .
				"WHERE preference.name = 'play_type' AND user_preference.preference = preference.id " .
				"AND now_playing.user = user_preference.user AND user_preference.value='downsample'";
			$db_results = Dba::read($sql);
			$results = Dba::fetch_row($db_results);

			// Current number of active streams (current is already
			// in now playing, worst case make it 1)
			$active_streams = intval($results[0]);
			if (!$active_streams) { $active_streams = '1'; }
			debug_event('transcode', "Active streams: $active_streams", 5);

			// If only one user, they'll get all available.
			// Otherwise split up equally.
			$sample_rate = floor($max_bitrate / $active_streams);

			// If min_bitrate is set, then we'll exit if the
			// bandwidth would need to be lower.
			if ($min_bitrate > 1 AND ($max_bitrate / $active_streams) < $min_bitrate) {
				debug_event('transcode', "Max bandwidth already allocated. Active streams: $active_streams", 2);
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				exit();
			}
			else {
				$sample_rate = floor($max_bitrate / $active_streams);
			} // end else

			// Never go over the user's sample rate
			if ($sample_rate > $user_sample_rate) { $sample_rate = $user_sample_rate; }

			debug_event('transcode', "Downsampling to $sample_rate", 5);

		} // end if we've got bitrates
		else {
			$sample_rate = $user_sample_rate;
		}

		/* Validate the bitrate */
		$sample_rate = self::validate_bitrate($sample_rate);

		// Never upsample a song
		if ($song->resampled && ($sample_rate * 1000) > $song->bitrate) {
			$sample_rate = self::validate_bitrate($song->bitrate / 1000);
		}

		// Set the new size for the song (in bytes)
		$song->size  = floor($sample_rate * $song->time * 125);

		/* Get Offset */
		$offset   = ($start * $song->time) / $song->size;
		$offsetmm = floor($offset / 60);
		$offsetss = floor($offset - ($offsetmm * 60));
		// If flac then format it slightly differently
		// HACK
		if ($song->transcoded_from == 'flac') { 
			$offset = sprintf('%02d:%02d', $offsetmm, $offsetss);
		} 
		else { 
			$offset = sprintf('%02d.%02d', $offsetmm, $offsetss);
		} 

		/* Get EOF */
		$eofmm  = floor($song->time / 60);
		$eofss  = floor($song->time - ($eofmm * 60));
		$eof    = sprintf('%02d.%02d', $eofmm, $eofss);

		$song_file = scrub_arg($song->file);

		$transcode_command = $song->stream_cmd();
		if ($transcode_command == null) {
			debug_event('downsample', 'song->stream_cmd() returned null', 2);
			return null;
		}

		$string_map = array(
			'%FILE%'   => $song_file,
			'%OFFSET%' => $offset,
			'%OFFSET_MM%' => $offsetmm,
			'%OFFSET_SS%' => $offsetss,
			'%EOF%'    => $eof,
			'%EOF_MM%' => $eofmm,
			'%EOF_SS%' => $eofss,
			'%SAMPLE%' => $sample_rate
		);

		foreach ($string_map as $search => $replace) {
			$transcode_command = str_replace($search, $replace, $transcode_command, $ret);
			if (!$ret) {
				debug_event('downsample', "$search not in downsample command", 5);
			}
		}

		debug_event('downsample', "Downsample command: $transcode_command", 3);

		$fp = popen($transcode_command, 'rb');

		// Return our new handle
		return $fp;

	} // start_downsample

	/**
	 * validate_bitrate
	 * this function takes a bitrate and returns a valid one
	 */
	public static function validate_bitrate($bitrate) {

		/* Round to standard bitrates */
		$sample_rate = 16*(floor($bitrate/16));

		return $sample_rate;

	} // validate_bitrate


	/**
 	 * gc_now_playing
	 * This will garbage collect the now playing data,
	 * this is done on every play start
	 */
	public static function gc_now_playing() {

		// Remove any now playing entries for session_streams that have been GC'd
		$sql = "DELETE FROM `now_playing` USING `now_playing` " .
			"LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " .
			"WHERE `session_stream`.`id` IS NULL OR `now_playing`.`expire` < '" . time() . "'";
		$db_results = Dba::write($sql);

	} // gc_now_playing

	/**
 	 * insert_now_playing
	 * This will insert the now playing data
	 * This fucntion is used by the /play/index.php song
	 * primarily, but could be used by other people
	 */
	public static function insert_now_playing($oid,$uid,$length,$sid,$type) {

		$time = intval(time()+$length);
		$session_id = Dba::escape($sid);
		$object_type = Dba::escape(strtolower($type));

		// Do a replace into ensuring that this client always only has a single row
		$sql = "REPLACE INTO `now_playing` (`id`,`object_id`,`object_type`, `user`, `expire`)" .
			" VALUES ('$session_id','$oid','$object_type', '$uid', '$time')";
		$db_result = Dba::write($sql);

	} // insert_now_playing

	 /**
	  * clear_now_playing
	  * There really isn't anywhere else for this function, shouldn't have deleted it in the first
	  * place
	  */
	public static function clear_now_playing() {

		$sql = "TRUNCATE `now_playing`";
		$db_results = Dba::write($sql);

		return true;

	} // clear_now_playing

	/**
	 * get_now_playing
	 * This returns the now playing information
	 */
	public static function get_now_playing($filter=NULL) {

		$sql = "SELECT `session_stream`.`agent`,`now_playing`.* " .
			"FROM `now_playing` " .
			"LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " .
			"ORDER BY `now_playing`.`expire` DESC";
		$db_results = Dba::read($sql);

		$results = array();

		while ($row = Dba::fetch_assoc($db_results)) {
			$type = $row['object_type'];
			$media = new $type($row['object_id']);
			$media->format();
			$client = new User($row['user']);
			$results[] = array('media'=>$media,'client'=>$client,'agent'=>$row['agent'],'expire'=>$row['expire']);
		} // end while

		return $results;

	} // get_now_playing

	/**
 	 * check_lock_media
	 * This checks to see if the media is already being played, if it is then it returns false
	 * else return true
	 */
	public static function check_lock_media($media_id,$type) {

		$media_id = Dba::escape($media_id);
		$type = Dba::escape($type);

		$sql = "SELECT `object_id` FROM `now_playing` WHERE `object_id`='$media_id' AND `object_type`='$type'";
		$db_results = Dba::read($sql);

		if (Dba::num_rows($db_results)) {
			debug_event('Stream','Unable to play media currently locked by another user','3');
			return false;
		}

		return true;

	} // check_lock_media

	/**
	 * auto_init
	 * This is called on class load it sets the session
	 */
	public static function _auto_init() {

		// Generate the session ID
		self::$session = md5(uniqid(rand(), true));

	} // auto_init

	/**
	 * run_playlist_method
	 * This takes care of the different types of 'playlist methods'. The
	 * reason this is here is because it deals with streaming rather than
	 * playlist mojo. If something needs to happen this will echo the
	 * javascript required to cause a reload of the iframe.
	 */
	public static function run_playlist_method() {

		// If this wasn't ajax included run away
		if (!defined('AJAX_INCLUDE')) { return false; }

		// If we're doin the flash magic then run away as well
		if (Config::get('play_type') == 'xspf_player') { return false; }

		switch (Config::get('playlist_method')) {
			default:
			case 'clear':
			case 'default':
				return true;
			break;
			case 'send':
				$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket';
			break;
			case 'send_clear':
				$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=basket&playlist_method=clear';
			break;
		} // end switch on method

		// Load our javascript
		echo "<script type=\"text/javascript\">";
		echo "reloadUtil('".$_SESSION['iframe']['target']."');";
		echo "</script>";

	} // run_playlist_method

	/**
	 * get_base_url
	 * This returns the base requirements for a stream URL this does not include anything after the index.php?sid=????
	 */
	public static function get_base_url() {

		if (Config::get('require_session')) {
			$session_string = 'ssid=' . Stream::get_session() . '&';
		}

		$web_path = Config::get('web_path');

		if (Config::get('force_http_play') OR !empty(self::$force_http)) {
			$web_path = str_replace("https://", "http://",$web_path);
		}
		if (Config::get('http_port') != '80') {
			if (preg_match("/:(\d+)/",$web_path,$matches)) {
				$web_path = str_replace(':' . $matches['1'],':' . Config::get('http_port'),$web_path);
			}
			else {
				$web_path = str_replace($_SERVER['HTTP_HOST'],$_SERVER['HTTP_HOST'] . ':' . Config::get('http_port'),$web_path);
			}
		}

		$url = $web_path . "/play/index.php?$session_string";

		return $url;

	} // get_base_url

} //end of stream class

?>
