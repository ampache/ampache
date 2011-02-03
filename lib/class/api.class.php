<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Api Class
 *
 * PHP version 5
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
 * @category	Api
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
*/

/**
 * API Class
 *
 * This handles functions relating to the API written for ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 *
 * @category	Api
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	Release: 3.6
 * @link	http://www.ampache.org/
 * @since	Class available since Release 1.0
 */
class Api {

	public static $version = '350001';

	private static $browse = null;

	/**
 	 * constructor
	 * This really isn't anything to do here, so it's private
	 */
	private function __construct() {

		// Rien a faire

	} // constructor

	/**
	 * _auto_init
	 * Automatically called when this class is loaded.
	 */
	public static function _auto_init() {
		if (is_null(self::$browse)) {
			self::$browse = new Browse();
		}
	}

	/**
	 * set_filter
	 * This is a play on the browse function, it's different as we expose
	 * the filters in a slightly different and vastly simpler way to the 
	 * end users--so we have to do a little extra work to make them work 
	 * internally.
	 */
	public static function set_filter($filter,$value) {

		if (!strlen($value)) { return false; }

		switch ($filter) {
			case 'add':
				// Check for a range, if no range default to gt
				if (strpos($value,'/')) {
					$elements = explode('/',$value);
					self::$browse->set_filter('add_lt',strtotime($elements['1']));
					self::$browse->set_filter('add_gt',strtotime($elements['0']));
				}
				else {
					self::$browse->set_filter('add_gt',strtotime($value));
				}
			break;
			case 'update':
				// Check for a range, if no range default to gt
				if (strpos($value,'/')) {
					$elements = explode('/',$value);
					self::$browse->set_filter('update_lt',strtotime($elements['1']));
					self::$browse->set_filter('update_gt',strtotime($elements['0']));
				}
				else {
					self::$browse->set_filter('update_gt',strtotime($value));
				}
			break;
			case 'alpha_match':
				self::$browse->set_filter('alpha_match',$value);
			break;
			case 'exact_match':
				self::$browse->set_filter('exact_match',$value);
			break;
			default:
				// Rien a faire
			break;
		} // end filter

		return true;

	} // set_filter

	/**
	 * handshake
	 * This is the function that handles the verifying a new handshake
	 * this takes a timestamp, auth key, and client IP. Optionally it
	 * can take a username, if non is passed the ACL must be non-use
	 * specific
	 */
	public static function handshake($input) {

		$timestamp = $input['timestamp'];
		$passphrase = $input['auth'];
		$ip = $_SERVER['REMOTE_ADDR'];
		$username = $input['user'];
		$version = $input['version'];

			// Let them know we're attempting
			debug_event('API',"Attempting Handshake IP:$ip User:$username Version:$version",'5');

		if (intval($version) < self::$version) {
			debug_event('API','Login Failed version too old','1');
			Error::add('api','Login Failed version too old');
			return false;
		}

		// If the timestamp is over 2hr old sucks to be them
		if ($timestamp < (time() - 14400)) {
			debug_event('API','Login Failed, timestamp too old','1');
			Error::add('api','Login Failed, timestamp too old');
			return false;
		}

		// First we'll filter by username and IP
		if (!trim($username)) {
			$user_id = '-1';
		}
		else {
			$client = User::get_from_username($username);
			$user_id =$client->id;
		}

		// Clean incomming variables
		$user_id 	= Dba::escape($user_id);
		$timestamp 	= intval($timestamp);
		$ip 		= inet_pton($ip);

		// Log this attempt
		debug_event('API','Login Attempt, IP:' . inet_ntop($ip) . ' Time:' . $timestamp . ' User:' . $username . '(' . $user_id . ') Auth:' . $passphrase,'1');

		$ip = Dba::escape($ip);

		// Run the query and return the passphrases as we'll have to mangle them
		// to figure out if they match what we've got
		$sql = "SELECT * FROM `access_list` " .
			"WHERE `type`='rpc' AND (`user`='$user_id' OR `access_list`.`user`='-1') " .
			"AND `start` <= '$ip' AND `end` >= '$ip'";
		$db_results = Dba::read($sql);

		while ($row = Dba::fetch_assoc($db_results)) {

			// Now we're sure that there is an ACL line that matches this user or ALL USERS,
			// pull the users password and then see what we come out with
			$sql = "SELECT * FROM `user` WHERE `id`='$user_id'";
			$user_results = Dba::read($sql);

			$row = Dba::fetch_assoc($user_results);

			if (!$row['password']) {
				debug_event('API','Unable to find user with username of ' . $user_id,'1');
				Error::add('api','Invalid Username/Password');
				return false;
			}

			$sha1pass = hash('sha256',$timestamp . $row['password']);

			if ($sha1pass === $passphrase) {
				// Create the Session, in this class for now needs to be moved
				$data['username']	= $client->username;
				$data['type']		= 'api';
				$data['value']		= $timestamp;
				$token = vauth::session_create($data);

				// Insert the token into the streamer
				Stream::insert_session($token,$client->id);
				debug_event('API','Login Success, passphrase matched','1');

				// We need to also get the 'last update' of the catalog information in an RFC 2822 Format
				$sql = "SELECT MAX(`last_update`) AS `update`,MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`";
				$db_results = Dba::read($sql);
				$row = Dba::fetch_assoc($db_results);

				// Now we need to quickly get the totals of songs
				$sql = "SELECT COUNT(`id`) AS `song`,COUNT(DISTINCT(`album`)) AS `album`," .
					"COUNT(DISTINCT(`artist`)) AS `artist` FROM `song`";
				$db_results = Dba::read($sql);
				$counts = Dba::fetch_assoc($db_results);

				// Next the video counts
				$sql = "SELECT COUNT(`id`) AS `video` FROM `video`";
				$db_results = Dba::read($sql);
				$vcounts = Dba::fetch_assoc($db_results);

				$sql = "SELECT COUNT(`id`) AS `playlist` FROM `playlist`";
				$db_results = Dba::read($sql);
				$playlist = Dba::fetch_assoc($db_results);

				echo xmlData::keyed_array(array('auth'=>$token,
					'api'=>self::$version,
					'update'=>date("c",$row['update']),
					'add'=>date("c",$row['add']),
					'clean'=>date("c",$row['clean']),
					'songs'=>$counts['song'],
					'albums'=>$counts['album'],
					'artists'=>$counts['artist'],
					'playlists'=>$playlist['playlist'],
					'videos'=>$vcounts['video']));
			} // match

		} // end while

		debug_event('API','Login Failed, unable to match passphrase','1');
		xmlData::error('401',_('Error Invalid Handshake - ') . _('Invalid Username/Password'));

	} // handshake

	/**
 	 * ping
	 * This can be called without being authenticated, it is useful for determining if what the status
	 * of the server is, and what version it is running/compatible with
	 */
	public static function ping($input) {

		$xmldata = array('server'=>Config::get('version'),'version'=>Api::$version,'compatible'=>'350001');

		// Check and see if we should extend the api sessions (done if valid sess is passed)
		if (vauth::session_exists('api', $input['auth'])) {
			vauth::session_extend($input['auth']);
			$xmldata = array_merge(array('session_expire'=>date("r",time()+Config::get('session_length')-60)),$xmldata);
		}

		debug_event('API','Ping Received from ' . $_SERVER['REMOTE_ADDR'] . ' :: ' . $input['auth'],'5');

		ob_end_clean();
		echo xmlData::keyed_array($xmldata);

	} // ping

	/**
	 * artists
	 * This takes a collection of inputs and returns
	 * artist objects. This function is deprecated!
	 * //DEPRECATED
	 */
	public static function artists($input) {

		self::$browse->reset_filters();
		self::$browse->set_type('artist');
		self::$browse->set_sort('name','ASC');

		$method = $input['exact'] ? 'exact_match' : 'alpha_match';
		Api::set_filter($method,$input['filter']);
		Api::set_filter('add',$input['add']);
		Api::set_filter('update',$input['update']);

		// Set the offset
		xmlData::set_offset($input['offset']);
		xmlData::set_limit($input['limit']);

		$artists = self::$browse->get_objects();
		// echo out the resulting xml document
		ob_end_clean();
		echo xmlData::artists($artists);

	} // artists

	/**
	 * artist
	 * This returns a single artist based on the UID of said artist
	 * //DEPRECATED
	 */
	public static function artist($input) {

		$uid = scrub_in($input['filter']);
		echo xmlData::artists(array($uid));

	} // artist

	/**
	 * artist_albums
	 * This returns the albums of an artist
	 */
	public static function artist_albums($input) {

		$artist = new Artist($input['filter']);

		$albums = $artist->get_albums();

		// Set the offset
		xmlData::set_offset($input['offset']);
		xmlData::set_limit($input['limit']);
		ob_end_clean();
		echo xmlData::albums($albums);

	} // artist_albums

	/**
	 * artist_songs
	 * This returns the songs of the specified artist
	 */
	public static function artist_songs($input) {

		$artist = new Artist($input['filter']);
		$songs = $artist->get_songs();

		// Set the offset
		xmlData::set_offset($input['offset']);
		xmlData::set_limit($input['limit']);
		ob_end_clean();
		echo xmlData::songs($songs);

	} // artist_songs

	/**
 	 * albums
	 * This returns albums based on the provided search filters
	 */
	public static function albums($input) {

		self::$browse->reset_filters();
		self::$browse->set_type('album');
		self::$browse->set_sort('name','ASC');
		$method = $input['exact'] ? 'exact_match' : 'alpha_match';
		Api::set_filter($method,$input['filter']);
		Api::set_filter('add',$input['add']);
		Api::set_filter('update',$input['update']);

		$albums = self::$browse->get_objects();

		// Set the offset
		xmlData::set_offset($input['offset']);
		xmlData::set_limit($input['limit']);
		ob_end_clean();
		echo xmlData::albums($albums);

	} // albums

	/**
	 * album
	 * This returns a single album based on the UID provided
	 */
	public static function album($input) {

		$uid = scrub_in($input['filter']);
		echo xmlData::albums(array($uid));

	} // album

	/**
	 * album_songs
	 * This returns the songs of a specified album
	 */
	public static function album_songs($input) {

			$album = new Album($input['filter']);
			$songs = $album->get_songs();

			// Set the offset
			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::songs($songs);

	} // album_songs

	/**
	 * tags
	 * This returns the tags based on the specified filter
	 */
	public static function tags($input) {

			self::$browse->reset_filters();
			self::$browse->set_type('tag');
			self::$browse->set_sort('name','ASC');

			$method = $input['exact'] ? 'exact_match' : 'alpha_match';
			Api::set_filter($method,$input['filter']);
			$tags = self::$browse->get_objects();

			// Set the offset
			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::tags($tags);

	} // tags

	/**
	 * tag
	 * This returns a single tag based on UID
	 */
	public static function tag($input) {

			$uid = scrub_in($input['filter']);
			ob_end_clean();
			echo xmlData::tags(array($uid));

	} // tag

	/**
	 * tag_artists
	 * This returns the artists associated with the tag in question as defined by the UID
	 */
	public static function tag_artists($input) {

			$artists = Tag::get_tag_objects('artist',$input['filter']);

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::artists($artists);

	} // tag_artists

	/**
	 * tag_albums
	 * This returns the albums associated with the tag in question
	 */
	public static function tag_albums($input) {

			$albums = Tag::get_tag_objects('album',$input['filter']);

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::albums($albums);

	} // tag_albums

	/**
	 * tag_songs
	 * returns the songs for this tag
	 */
	public static function tag_songs($input) {

			$songs = Tag::get_tag_objects('song',$input['filter']);

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::songs($songs);

	} // tag_songs

	/**
	 * songs
	 * Returns songs based on the specified filter
	 */
	public static function songs($input) {

			self::$browse->reset_filters();
			self::$browse->set_type('song');
			self::$browse->set_sort('title','ASC');

			$method = $input['exact'] ? 'exact_match' : 'alpha_match';
			Api::set_filter($method,$input['filter']);
			Api::set_filter('add',$input['add']);
			Api::set_filter('update',$input['update']);

			$songs = self::$browse->get_objects();

			// Set the offset
			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::songs($songs);

	} // songs

	/**
	 * song
	 * returns a single song
	 */
	public static function song($input) {

			$uid = scrub_in($input['filter']);

			ob_end_clean();
			echo xmlData::songs(array($uid));

	} // song

	/**
	 * url_to_song
	 * This takes a url and returns the song object in question
	 */
	public static function url_to_song($input) {

			// Don't scrub in we need to give her raw and juicy to the function
			$url = $input['url'];

			$song_id = Song::parse_song_url($url);

			ob_end_clean();
			echo xmlData::songs(array($song_id));

	} // url_to_song

	/**
 	 * playlists
	 * This returns playlists based on the specified filter
	 */
	public static function playlists($input) {

			self::$browse->reset_filters();
			self::$browse->set_type('playlist');
			self::$browse->set_sort('name','ASC');

			$method = $input['exact'] ? 'exact_match' : 'alpha_match';
			Api::set_filter($method,$input['filter']);

			$playlist_ids = self::$browse->get_objects();

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			ob_end_clean();
			echo xmlData::playlists($playlist_ids);

	} // playlists

	/**
	 * playlist
	 * This returns a single playlist
	 */
	public static function playlist($input) {

			$uid = scrub_in($input['filter']);

			ob_end_clean();
			echo xmlData::playlists(array($uid));

	} // playlist

	/**
	 * playlist_songs
	 * This returns the songs for a playlist
	 */
	public static function playlist_songs($input) {

			$playlist = new Playlist($input['filter']);
			$items = $playlist->get_items();

			foreach ($items as $object) {
				if ($object['type'] == 'song') {
					$songs[] = $object['object_id'];
				}
			} // end foreach

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);
			ob_end_clean();
			echo xmlData::songs($songs);

	} // playlist_songs

	/**
	 * search_songs
	 * This returns the songs and returns... songs
	 */
	public static function search_songs($input) {

			$array['s_all'] = $input['filter'];
			ob_end_clean();

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			//WARNING!!! This is a horrible hack that has to be here because
			//Run search references these variables, ooh the huge manatee
			unset($input['limit'],$input['offset']);

			$results = run_search($array);

			echo xmlData::songs($results);

	} // search_songs

	/**
	 * videos
	 * This returns video objects!
	 */
	public static function videos($input) {

			self::$browse->reset_filters();
			self::$browse->set_type('video');
			self::$browse->set_sort('title','ASC');

			$method = $input['exact'] ? 'exact_match' : 'alpha_match';
			Api::set_filter($method,$input['filter']);

			$video_ids = self::$browse->get_objects();

			xmlData::set_offset($input['offset']);
			xmlData::set_limit($input['limit']);

			echo xmlData::videos($video_ids);

	} // videos

	/**
	 * video
	 * This returns a single video
	 */
	public static function video($input) {

			$video_id = scrub_in($input['filter']);

			echo xmlData::videos(array($video_id));


	} // video

	/**
	 * localplay
	 * This is for controling localplay
	 */
	public static function localplay($input) {

			// Load their localplay instance
			$localplay = new Localplay(Config::get('localplay_controller'));
			$localplay->connect();

			switch ($input['command']) {
				case 'next':
				case 'prev':
				case 'play':
				case 'stop':
					$result_status = $localplay->$input['command']();
					$xml_array = array('localplay'=>array('command'=>array($input['command']=>make_bool($result_status))));
					echo xmlData::keyed_array($xml_array);
				break;
				default:
					// They are doing it wrong
					echo xmlData::error('405',_('Invalid Request'));
				break;
			} // end switch on command

	} // localplay

	/**
	 * democratic
	 * This is for controlling democratic play
	 */
	public static function democratic($input) {

			// Load up democratic information
			$democratic = Democratic::get_current_playlist();
			$democratic->set_parent();

			switch ($input['method']) {
				case 'vote':
					$type = 'song';
					$media = new $type($input['oid']);
					if (!$media->id) {
						echo xmlData::error('400',_('Media Object Invalid or Not Specified'));
						break;
					}
					$democratic->vote(array(array('song',$media->id)));

					// If everything was ok
					$xml_array = array('action'=>$input['action'],'method'=>$input['method'],'result'=>true);
					echo xmlData::keyed_array($xml_array);
				break;
				case 'devote':
					$type = 'song';
					$media = new $type($input['oid']);
					if (!$media->id) {
						echo xmlData::error('400',_('Media Object Invalid or Not Specified'));
					}

					$uid = $democratic->get_uid_from_object_id($media->id,$type);
					$democratic->remove_vote($uid);

					// Everything was ok
					$xml_array = array('action'=>$input['action'],'method'=>$input['method'],'result'=>true);
					echo xmlData::keyed_array($xml_array);
				break;
				case 'playlist':
					$objects = $democratic->get_items();
					Song::build_cache($democratic->object_ids);
					Democratic::build_vote_cache($democratic->vote_ids);
					xmlData::democratic($objects);
				break;
				case 'play':
					$url = $democratic->play_url();
					$xml_array = array('url'=>$url);
					echo xmlData::keyed_array($xml_array);
				break;
				default:
					echo xmlData::error('405',_('Invalid Request'));
			break;
		} // switch on method

	} // democratic

} // API class
?>
