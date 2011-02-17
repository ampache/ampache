<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * vainfo Class
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
 * @category	vainfo
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

/**
 * vainfo Class
 *
 * This class takes the information pulled from getID3 and returns it in a
 * Ampache friendly way.
 *
 * @category	vainfo
 * @package	Ampache
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	Release: 3.6
 * @link	http://www.ampache.org/
 * @since	Class available since Release 1.0
 */
class vainfo {

	// {{{ property

	/**
	 * Default encoding
	 *
	 * @var	string
	 */
	public $encoding	= '';

	/**
	 * Default id3v1 encoding
	 *
	 * @var	string
	 */
	public $encoding_id3v1	= 'ISO-8859-1';

	/**
	 * Default id3v2 encoding
	 *
	 * @var	string
	 */
	public $encoding_id3v2 = 'ISO-8859-1';

	/* Loaded Variables */
	/**
	 * Filename
	 *
	 * @var	string
	 */
	public $filename	= '';

	/**
	 * Media type
	 *
	 * @var	string
	 */ 
	public $type		= '';

	/**
	 * Media tags
	 *
	 * @var	array
	 */
	public $tags		= array();

	/* Internal Information */
	/**
	 * GetID3 analyzed data.
	 *
	 * @var	array
	 */
	protected $_raw 		= array();

	/**
	 * GetID3 object
	 *
	 * @var	object
	 */
	protected $_getID3 	= '';

	/**
	 * Iconv use flag
	 *
	 * @var	boolean
	 */
	protected $_iconv		= false;

	/**
	 * File encoding charset
	 *
	 * @var	string
	 */
	protected $_file_encoding	= '';

	/**
	 * File pattern
	 *
	 * @var	string
	 */
	protected $_file_pattern	= '';

	/**
	 * Directory pattern
	 *
	 * @var	string
	 */
	protected $_dir_pattern	= '';

	/* Internal Private */
	/**
	 * Pathinfo results array
	 *
	 * @var	array
	 */
	private $_pathinfo;

	/**
	 * Tag broken flag. If tag is broken, return true.
	 *
	 * @var	boolean
	 */
	private $_broken = false;

	// }}}

	/**
	 * Constructor
	 *
	 * This function just sets up the class, it doesn't
	 * actually pull the information
	 *
	 * @todo	Some mp3 is still broken...
	 * @param	string	$file	filename
	 * @param	string	$encoding	Default encode character set
	 * @param	string	$encoding_id3v1	Default id3v1 encode character set
	 * @param	string	$encoding_iv3v2	Default id3v2 encode character set
	 * @param	string	$dir_pattern	Directory pattern
	 * @param	string	$file_pattern	File pattern
	 * @return	mixed	If can't analyze file, return false. default return: void
	 */
	public function __construct($file, $encoding = null, $encoding_id3v1 = null, $encoding_id3v2 = null, $dir_pattern, $file_pattern) {

		/* Check for ICONV */
		if (function_exists('iconv') && Config::get('use_iconv') == "1") {
			$this->_iconv = true;
		}
		else {
			$this->_iconv = false;
		}

		$this->filename = $file;
		if ($encoding) {
			$this->encoding = $encoding;
		}
		else {
			$this->encoding = Config::get('site_charset');
		}

		/* These are needed for the filename mojo */
		$this->_file_pattern = $file_pattern;
		$this->_dir_pattern  = $dir_pattern;

		if(strtoupper(substr(PHP_OS,0,3)) == 'WIN') {
			$this->_pathinfo = str_replace('%3A', ':', urlencode($this->filename));
			$this->_pathinfo = pathinfo(str_replace('%5C', '\\', $this->_pathinfo));
		}
		else {
			$this->_pathinfo = pathinfo(str_replace('%2F', '/', urlencode($this->filename)));
		}
		$this->_pathinfo['extension'] = strtolower($this->_pathinfo['extension']);

		// Initialize getID3 engine
		$this->_getID3 = new getID3();

		$this->_getID3->option_md5_data		= false;
		$this->_getID3->option_md5_data_source	= false;
		$this->_getID3->option_tags_html	= false;
		$this->_getID3->option_extra_info	= true;
		$this->_getID3->option_tag_lyrics3	= true;
		$this->_getID3->option_tags_process	= true;
		$this->_getID3->encoding		= $this->encoding;

		// get id3tag encoding (try to work around off-spec id3v1 tags)
		try {
			$this->_raw = $this->_getID3->analyze($file);
		}
		catch (Exception $error) {
			debug_event('Getid3()',"Broken file detected $file - " . $error->message,'1');
			$this->_broken = true;
			return false;
		}

		/* Detect tag order */
		$tag_order = (array)Config::get('getid3_tag_order');
		$id3v1 = array_search('id3v1', $tag_order);
		$id3v2 = array_search('id3v2', $tag_order);

		/* Use default mb_detect_order in php.ini or not */
		if (Config::get('mb_detect_override') == "1") {
			$mb_order = Config::get('mb_detect_order');
		}
		elseif (function_exists('mb_detect_order')) {
			$mb_order = implode(", ", mb_detect_order());
		}
		else {
			$mb_order = "auto";
		}

		if ($id3v1 < $id3v2) {
			$id3v = $tag_order[$id3v1];
		}
		elseif ($id3v1 > $id3v2) {
			$id3v = $tag_order[$id3v2];
		}
		else {
			$id3v = 'id3v1';
		}
		debug_event('vainfo', "Tag order -> ".$id3v, 5);

		if ($encoding_id3v1 && $encoding_id3v2) {
			if ($id3v1 > $id3v2) {
				$this->encoding_id3v1 = $encoding_id3v1;
				$id3v = 'id3v1';
			}
			elseif ($id3v1 < $id3v2) {
				$this->encoding_id3v2 = $encoding_id3v2;
				$id3v = 'id3v2';
			}
			else {
				$this->encoding_id3v1 = $encoding_id3v1;
				$id3v = 'id3v1';
			}
		}
		elseif (!$encoding_id3v1 && $encoding_id3v2) {
			$this->encoding_id3v2 = $encoding_id3v2;
			$id3v = 'id3v2';
		}
		elseif ($encoding_id3v1 && !$encoding_id3v2) {
			$this->encoding_id3v1 = $encoding_id3v1;
			$id3v = 'id3v1';
		}
		elseif (function_exists('mb_detect_encoding')) {
			debug_event('vainfo', "id3v -> $id3v", 5);
			$encodings = array();
			$tags = array('artist', 'album', 'genre', 'title');
			foreach ($tags as $tag) {
				if (strcmp($id3v, 'id3v1') == 0) {
					if ($value = $this->_raw[$id3v][$tag]) {
						debug_event('vainfo', 'try to detect encoding id3v1', 5);
						$encodings[mb_detect_encoding($value, $mb_order, true)]++;
					} 
				}
				else {
					debug_event('vainfo', 'try to detect encoding id3v2', 5);
					if ($values = $this->_raw[$id3v]['comments'][$tag]) {
						foreach ($this->_raw[$id3v]['comments'][$tag] as $value) {
							$encodings[mb_detect_encoding($value, $mb_order, true)]++;
						}
					}
				}
			}

			debug_event('vainfo', 'encoding detection ('. $id3v .'): ' . print_r($encodings, true), 5);
			$high = 0;
			foreach ($encodings as $key => $value) {
				if ($value > $high) {
					if (strcmp($id3v, 'id3v1') == 0) {
						debug_event('vainfo', "\$encoding_id3v1 Set to $key",5);
						$encoding_id3v1 = $key;
					}
					else {
						debug_event('vainfo', "\$encoding_id3v2 Set to $key",5);
						$encoding_id3v2 = $key;
					}
					$high = $value;
				}
			}

			if (strcmp($id3v, 'id3v1') == 0) {
				if ($encoding_id3v1 != 'ASCII' && $encoding_id3v1 != '0') {
					debug_event('vainfo', "\$this->encoding_id3v1 Set to $encoding_id3v1",5);
					$this->encoding_id3v1 = $encoding_id3v1;
				}
				else {
					debug_event('vainfo', "\$this->encoding_id3v1 Set to ISO-8859-1",5);
					$this->encoding_id3v1 = 'ISO-8859-1';
				}
			}
			else {
				if ($encoding_id3v2 != 'ASCII' && $encoding_id3v2 != '0') {
					debug_event('vainfo', "\$this->encoding_id3v2 Set to $encoding_id3v2",5);
					$this->encoding_id3v2 = $encoding_id3v2;
				}
				else {
					debug_event('vainfo', "\$this->encoding_id3v2 Set to ISO-8859-1",5);
					$this->encoding_id3v2 = 'ISO-8859-1';
				}
			}

			debug_event('vainfo', 'encoding detection ('. $id3v .') selected ' .  $this->encoding_id3v1, 5);
			debug_event('vainfo', 'encoding detection ('. $id3v .') selected ' .  $this->encoding_id3v2, 5);
		}
		else {
			$this->encoding_id3v1 = 'ISO-8859-1';
			$this->encoding_id3v2 = 'ISO-8859-1';
		}

		$this->_getID3->encoding_id3v1 = $this->encoding_id3v1;
		$this->_getID3->encoding_id3v2 = $this->encoding_id3v2;

	} // vainfo


	/**
	 * get_info
	 * This function runs the various steps to gathering the metadata
	 */
	public function get_info() {

		// If this is broken, don't waste time figuring it out a second
		// time, just return their rotting carcass of a media file.
		if ($this->_broken) {
			$this->tags = $this->set_broken();
			return true;
		}

		/* Get the Raw file information */
		try {
			$this->_raw = $this->_getID3->analyze($this->filename);
		}
		catch (Exception $error) {
			debug_event('Getid3()',"Unable to catalog file:" . $error->message,'1');
		}

		/* Figure out what type of file we are dealing with */
		$this->type = $this->_get_type();

		$enabled_sources = (array)Config::get('metadata_order');

		if (in_array('filename', $enabled_sources)) {
			$this->tags['filename'] = $this->_parse_filename($this->filename);
		}

		if (in_array('getID3', $enabled_sources)) {
			$this->tags['getID3'] = $this->_get_tags();
		}

		$this->_get_plugin_tags();

	} // get_info

	/**
	 * get_tag_type
	 * This takes the result set and the tag_order defined in your config
	 * file and tries to figure out which tag type(s) it should use. If your
	 * tag_order doesn't match anything then it throws up its hands and uses
	 * everything.
	 */
	public static function get_tag_type($results, $config_key = 'metadata_order') {

		$order = (array)Config::get($config_key);

		/* Iterate through the defined key order adding them to an
		 * ordered array as we go.
		 */

		foreach($order as $key) {
			if ($results[$key]) {
				$returned_keys[] = $key;
			}
		}

		/* If we didn't find anything then default to everything.
		 */
		if (!isset($returned_keys)) {
			$returned_keys = array_keys($results);
			$returned_keys = sort($returned_keys);
		}

		return $returned_keys;

	} // get_tag_type

	/**
	 * clean_tag_info
	 * This function takes the array from vainfo along with the
	 * key we've decided on and the filename and returns it in a
	 * sanitized format that ampache can actually use
	 */
	public static function clean_tag_info($results, $keys, $filename = null) {

		$info = array();

		if ($filename) {
			$info['file'] = $filename;
		}

		// Iteration!
		foreach ($keys as $key) {
			$tags = $results[$key];

			$info['file']		= $info['file']
						? $info['file']
						: $tags['file'];


			$info['bitrate']	= $info['bitrate']
						? $info['bitrate']
						: intval($tags['bitrate']);

			$info['rate']		= $info['rate']
						? $info['rate']
						: intval($tags['rate']);

			$info['mode']		= $info['mode']
						? $info['mode']
						: $tags['mode'];

			$info['size']		= $info['size']
						? $info['size']
						: $tags['size'];

			$info['mime']		= $info['mime']
						? $info['mime']
						: $tags['mime'];

			$info['encoding']       = $info['encoding']
						? $info['encoding']
						: $tags['encoding'];

			$info['time']		= $info['time']
						? $info['time']
						: intval($tags['time']);

			$info['channels']	= $info['channels']
						? $info['channels']
						: $tags['channels'];

			/* These are used to generate the correct IDs later */
			$info['title']		= $info['title']
						? $info['title']
						: stripslashes(trim($tags['title']));

			$info['year']		= $info['year']
						? $info['year']
						: intval($tags['year']);

			$info['disk']		= $info['disk']
						? $info['disk']
						: intval($tags['disk']);

			$info['artist']		= $info['artist']
						? $info['artist']
						: trim($tags['artist']);

			$info['album']		= $info['album']
						? $info['album']
						: trim($tags['album']);

			// multiple genre support
			if ((!$info['genre']) && $tags['genre']) {
				if (!is_array($tags['genre'])) {
					// not all tag formats will return an array, but we need one
					$info['genre'][] = trim($tags['genre']);
				}
				else {
					// if we trim the array we lose everything after 1st entry
					foreach ($tags['genre'] as $genre) {
						$info['genre'][] = trim($genre);
					}
				}
			}

			$info['mb_trackid']	= $info['mb_trackid']
						? $info['mb_trackid']
						: trim($tags['mb_trackid']);

			$info['mb_albumid']	= $info['mb_albumid']
						? $info['mb_albumid']
						: trim($tags['mb_albumid']);

			$info['mb_artistid']	= $info['mb_artistid']
						? $info['mb_artistid']
						: trim($tags['mb_artistid']);

			/* @TODO language doesn't import from id3tag. @momo-i */
			$info['language']	= $info['language']
						? $info['language']
						: Dba::escape($tags['language']);

			$info['lyrics']		= $info['lyrics']
						? $info['lyrics']
						: str_replace(
							array("\r\n","\r","\n"),
							'<br />',
							strip_tags($tags['lyrics']));

			$info['track']		= $info['track']
						? $info['track']
						: intval($tags['track']);

			$info['resolution_x']	= $info['resolution_x']
						? $info['resolution_x']
						: intval($tags['resolution_x']);

			$info['resolution_y']	= $info['resolution_y']
						? $info['resolution_y']
						: intval($tags['resolution_y']);

			$info['audio_codec']	= $info['audio_codec']
						? $info['audio_codec']
						: Dba::escape($tags['audio_codec']);

			$info['video_codec']	= $info['video_codec']
						? $info['video_codec']
						: Dba::escape($tags['video_codec']);
		}

		// I really think this belongs somewhere else
		$slash_point = strpos($info['disk'], '/');
		if ($slash_point !== false) {
			$info['disk'] = substr($info['disk'], 0, $slash_point);
		}
			

		return $info;

	} // clean_tag_info

	/**
	 * _get_type
	 * This function takes the raw information and figures out
	 * what type of file we are dealing with for use by the tag
	 * function
	 */
	public function _get_type() {

		/* There are a few places that the file type can
		 * come from, in the end we trust the encoding
		 * type
		 */
		if ($type = $this->_raw['video']['dataformat']) {
			// Manually set the tag information
			if ($type == 'flv') {
				$this->_raw['tags']['flv'] = array();
			}
			if ($type == 'quicktime') {
				$this->_raw['tags']['quicktime'] = array();
			}
			if($type == 'mpeg' OR $type == 'mpg') {
				$this->_raw['tags']['mpeg'] = array();
			}
			if($type == 'asf') {
				$this->_raw['tags']['asf'] = array();
			}
			if($type == 'wmv') {
				$this->_raw['tags']['wmv'] = array();
			}
			else {
				$this->_raw['tags']['avi'] = array();
			}
			$type = $this->_clean_type($type);
			return $type;
		}
		if ($type = $this->_raw['audio']['streams']['0']['dataformat']) {
			$type = $this->_clean_type($type);
			return $type;
		}
		if ($type = $this->_raw['audio']['dataformat']) {
			$type = $this->_clean_type($type);
			return $type;
		}
		if ($type = $this->_raw['fileformat']) {
			$type = $this->_clean_type($type);
			return $type;
		}

		return false;

	} // _get_type


	/**
	 * _get_tags
	 * This function takes the raw information and the type and
	 * attempts to gather the tags and then normalize them into
	 * ['tag_name']['var'] = value
	 */
	public function _get_tags() {

		$results = array();

		/* The tags can come in many different shapes and colors
		 * depending on the encoding time of day and phase of the moon.
		 */
		foreach ($this->_raw['tags'] as $key => $tag_array) {
			switch ($key) {
				case 'vorbiscomment':
					debug_event('_get_tags', 'Parsing vorbis', '5');
					$results[$key] = $this->_parse_vorbiscomment($tag_array);
				break;
				case 'id3v1':
					debug_event('_get_tags', 'Parsing id3v1', '5');
					$results[$key] = $this->_parse_id3v1($tag_array);
				break;
				case 'id3v2':
					debug_event('_get_tags', 'Parsing id3v2', '5');
					$results[$key] = $this->_parse_id3v2($tag_array);
				break;
				case 'ape':
					debug_event('_get_tags', 'Parsing ape', '5');
					$results[$key] = $this->_parse_ape($tag_array);
				break;
				case 'quicktime':
					debug_event('_get_tags', 'Parsing quicktime', '5');
					$results[$key] = $this->_parse_quicktime($tag_array);
				break;
				case 'riff':
					debug_event('_get_tags', 'Parsing riff', '5');
					$results[$key] = $this->_parse_riff($tag_array);
				break;
				case 'flv':
					debug_event('_get_tags', 'Parsing flv', '5');
					$results[$key] = $this->_parse_flv($this->_raw);
				break;
				case 'mpg':
				case 'mpeg':
					debug_event('_get_tags', 'Parsing MPEG', '5');
					$results['mpeg'] = $this->_parse_mpg($this->_raw);
				break;
				case 'asf':
				case 'wmv':
					debug_event('_get_tags', 'Parsing WMV/WMA/ASF', '5');
					$results['asf'] = $this->_parse_wmv($this->_raw);
				break;
				case 'avi':
					debug_event('_get_tags', 'Parsing avi', '5');
					$results[$key] = $this->_parse_avi($this->_raw);
				break;
				case 'lyrics3':
					debug_event('_get_tags', 'Parsing lyrics3', '5');
					$results[$key] = $this->_parse_lyrics($tag_array);
				break;
				default:
					debug_event('vainfo','Error: Unable to determine tag type of ' . $key . ' for file ' . $this->filename . ' Assuming id3v2','5');
					$results[$key] = $this->_parse_id3v2($this->_raw['id3v2']['comments']);
				break;
			} // end switch
		} // end foreach


		$cleaned = self::clean_tag_info($results, self::get_tag_type($results, 'getid3_tag_order'), $this->filename);
		$cleaned = array_merge($cleaned, $this->_get_info());
		$cleaned['raw'] = $results;

		return $cleaned;

	} // _get_tags

	/**
	 * _get_plugin_tags
	 * Get additional metadata from plugins
	 */
	private function _get_plugin_tags() {
		$tag_order = Config::get('metadata_order');
		if (!is_array($tag_order)) {
			$tag_order = array($tag_order);
		}

		$plugin_names = Plugin::get_plugins('get_metadata');
		foreach ($tag_order as $key => $tag_source) {
			if (in_array($tag_source, $plugin_names)) {
				$plugin = new Plugin($tag_source);
				if ($plugin->load()) {
					$this->tags[$tag_source] = $plugin->_plugin->get_metadata(self::clean_tag_info($this->tags, self::get_tag_type($this->tags), $this->filename));
				}
			}
		}
	} // _get_plugin_tags

	/**
	 * _get_info
	 * Gather and return the general information about a song (vbr/cbr,
	 * sample rate, channels, etc.)
	 */
	private function _get_info() {

		$array = array();

		/* Try to pull the information directly from
		 * the audio array
		 */
		if ($this->_raw['audio']['bitrate_mode']) {
			$array['mode'] 	= $this->_raw['audio']['bitrate_mode'];
			if ($array['mode'] == 'con') {
				$array['mode'] = 'cbr';
			}
		}
		if ($this->_raw['audio']['bitrate']) {
			$array['bitrate']	= $this->_raw['audio']['bitrate'];
		}
		if ($this->_raw['audio']['channels']) {
			$array['channels'] 	= intval($this->_raw['audio']['channels']);
		}
		if ($this->_raw['audio']['sample_rate']) {
			$array['rate']	= intval($this->_raw['audio']['sample_rate']);
		}
		if ($this->_raw['filesize']) {
			$array['size']	= intval($this->_raw['filesize']);
		}
		if ($this->_raw['encoding']) {
			$array['encoding']	= $this->_raw['encoding'];
		}
		if ($this->_raw['mime_type']) {
			$array['mime']		= $this->_raw['mime_type'];
		}
		if ($this->_raw['playtime_seconds']) {
			$array['time']	= $this->_raw['playtime_seconds'];
		}

		return $array;

	} // _get_info

	/**
	 * _clean_type
	 * This standardizes the type that we are given into a recognized type.
	 */
	private function _clean_type($type) {

		switch ($type) {
			case 'mp3':
			case 'mp2':
			case 'mpeg3':
				return 'mp3';
			break;
			case 'vorbis':
				return 'ogg';
			break;
			case 'flac':
			case 'flv':
			case 'mpg':
			case 'mpeg':
			case 'asf':
			case 'wmv':
			case 'avi':
			case 'quicktime':
				return $type;
			default:
				/* Log the fact that we couldn't figure it out */
				debug_event('vainfo','Unable to determine file type from ' . $type . ' on file ' . $this->filename,'5');
				return $type;
			break;
		} // end switch on type

	} // _clean_type

	/**
	 * _parse_lyrics
	 * This function takes a lyrics3 from getid3()
	 * nothing to do?
	 */
	private function _parse_lyrics($tags) {

		/* Results array */
		$array = array();

		/* go through them all! */
		foreach ($tags as $tag => $data) {
			if ($tag == 'unsynchedlyrics' || $tag == 'unsynchronised lyric') {
				$tag = 'lyrics';
			}
			$array[$tag] = $this->_clean_tag($data['0']);

		} // end foreach

		return $array;

	} // _parse_lyrics

	/**
	 * _parse_vorbiscomment
	 * This function takes a vorbiscomment from getid3() and then
	 * returns the elements translated using iconv if needed in a
	 * pretty little format
	 */
	private function _parse_vorbiscomment($tags) {

		/* Results array */
		$array = array();

		/* go through them all! */
		foreach ($tags as $tag=>$data) {

			/* We need to translate a few of these tags */
			switch ($tag) {

				case 'genre':
					// multiple genre support
					foreach($data as $foo) {
						$array['genre'][] = $this->_clean_tag($foo,'');
					}
				break;
				case 'tracknumber':
					$array['track']	= $this->_clean_tag($data['0']);
				break;
				case 'discnumber':
					$array['disk'] 	= $this->_clean_tag($data['0']);
				break;
				case 'date':
					$array['year']	= $this->_clean_tag($data['0']);
				break;
				default:
					$array[$tag] = $this->_clean_tag($data['0']);
				break;
			} // end switch

		} // end foreach

		return $array;

	} // _parse_vorbiscomment

	/**
	 * _parse_id3v1
	 * This function takes an id3v1 tag set from getid3() and then
	 * returns the elements (translated using iconv if needed) in a
	 * pretty little format.
	 */
	private function _parse_id3v1($tags) {

		$array = array();

		/* Go through all the tags */
		foreach ($tags as $tag=>$data) {

			/* This is our baseline for naming
			 * so no translation needed
			 */
			$array[$tag]	= $this->_clean_tag($data['0']);

		} // end foreach

		return $array;

	} // _parse_id3v1

	/**
	 * _parse_id3v2
	 * This function takes an id3v2 tag set from getid3() and then
	 * returns the elements (translated using iconv if needed) in a
	 * pretty little format.
	 */
	private function _parse_id3v2($tags) {

		$array = array();

		/* Go through the tags */
		foreach ($tags as $tag=>$data) {

			/**
			 * the new getid3 handles this differently
			 * so we now need to account for it :(
			 */
			switch ($tag) {
				case 'genre':
					// multiple genre support
					foreach($data as $genre) {
						$array['genre'][] = $this->_clean_tag($genre);
					}
				break;
				case 'pos':
					$el = explode('/', $data['0']);
					$array['disk'] = $el[0];
				break;
				case 'track_number':
					$array['track'] = $this->_clean_tag($data['0']);
				break;
				case 'comments':
					$array['comment'] = $this->_clean_tag($data['0']);
				break;
				case 'title':
					$array['title'] = $this->_clean_tag($data['0']);
				break;
				default:
					$array[$tag]	= $this->_clean_tag($data['0']);
				break;
			} // end switch on tag

		} // end foreach

		$id3v2 = $this->_raw['id3v2'];

		if(!empty($id3v2['UFID'])) {
			foreach ($id3v2['UFID'] as $ufid) {
				if ($ufid['ownerid'] == 'http://musicbrainz.org') {
					$array['mb_trackid'] = $this->_clean_tag($ufid['data']);
				}
			}

			for ($i = 0, $size = sizeof($id3v2['comments']['text']) ; $i < $size ; $i++) {
				if ($id3v2['TXXX'][$i]['description'] == 'MusicBrainz Album Id') {
					$array['mb_albumid'] = $this->_clean_tag($id3v2['comments']['text'][$i]);
				}
				elseif ($id3v2['TXXX'][$i]['description'] == 'MusicBrainz Artist Id') {
					$array['mb_artistid'] = $this->_clean_tag($id3v2['comments']['text'][$i]);
				}
			}
		}

		return $array;

	} // _parse_id3v2

	/**
	 * _parse_ape
	 * This function takes ape tags set by getid3() and then
	 * returns the elements translated using iconv if needed in a
	 * pretty little format
	 */
	private function _parse_ape($tags) {

		foreach ($tags as $tag=>$data) {
			switch ($tag) {

				case 'genre':
					// multiple genre support
					foreach($data as $genre) {
						$array['genre'][] = $this->_clean_tag($genre);
					}
				break;

				default:
					$array[$tag] = $this->_clean_tag($data['0'], $this->_file_encoding);
				break;
			} // end switch on tag

		} // end foreach tags

		return $array;

	} // _parse_ape

	/**
	 * _parse_riff
	 * this function takes the riff take information passed by getid3() and
	 * then reformats it so that it matches the other formats. May require iconv
	 */
	private function _parse_riff($tags) {

		foreach ($tags as $tag=>$data) {

			switch ($tag) {
				case 'product':
					$array['album'] = $this->_clean_tag($data['0'], $this->_file_encoding);
				break;
				default:
					$array[$tag] = $this->_clean_tag($data['0'], $this->_file_encoding);
				break;
			} // end switch on tag

		} // foreach tags

		return $array;

	} // _parse_riff

	/**
	 * _parse_quicktime
	 * this function takes the quicktime tags set by getid3() and then
	 * returns the elements translated using iconv if needed in a
	 * pretty little format
	 */
	private function _parse_quicktime($tags) {

		/* Results array */
		$array = array();

		/* go through them all! */
		foreach ($tags as $tag=>$data) {

			/* We need to translate a few of these tags */
			switch ($tag) {
				case 'creation_date':
					if (strlen($data['0']) > 4) {
						/* Weird Date format, attempt to normalize */
						$data['0'] = date("Y",strtotime($data['0']));
					}
					$array['year']  = $this->_clean_tag($data['0']);
				break;
			} // end switch

			$array[$tag] = $this->_clean_tag($data['0']);

		} // end foreach

		// Also add in any video related stuff we might find
		if (strpos($this->_raw['mime_type'], 'video') !== false) {
			$info = $this->_parse_avi($this->_raw);
			$info['video_codec'] = $this->_raw['quicktime']['ftyp']['fourcc'];
			$array = array_merge($info, $array);
		}

		return $array;

	} // _parse_quicktime

	/**
	 * _parse_avi
	 * This attempts to parse our the information on an avi file and present it in some
	 * kind of sane format, this is a little hard as these files don't have tags
	 */
	private function _parse_avi($tags) {

		$array = array();

		$array['title'] 		= urldecode($this->_pathinfo['filename']);
		$array['video_codec'] 		= $tags['video']['fourcc'];
		$array['audio_codec'] 		= $tags['audio']['dataformat'];
		$array['resolution_x']		= $tags['video']['resolution_x'];
		$array['resolution_y']		= $tags['video']['resolution_y'];
		$array['mime']			= $tags['mime_type'];
		$array['comment']		= $tags['video']['codec'];

		return $array;

	} // _parse_avi

	/**
	 * _parse_mpg
	 * This attempts to parse our the information on a mpg file and present it in some
	 * kind of sane format, this is a little hard as these files don't have tags
	 */
	private function _parse_mpg($tags) {

		$array = array();

		$array['title']			= urldecode($this->_pathinfo['filename']);
		$array['video_codec']		= $tags['video']['codec'];
		$array['audio_codec']		= $tags['audio']['dataformat'];
		$array['resolution_x']		= $tags['video']['resolution_x'];
		$array['resolution_y']		= $tags['video']['resolution_y'];
		$array['mime']			= $tags['mime_type'];
		$array['comment']		= $tags['video']['codec'];

		return $array;

	} // _parse_mpg

	/**
	 * _parse_wmv
	 * This attempts to parse our the information on a asf/wmv file and present it in some
	 * kind of sane format, this is a little hard as these files don't have tags
	 */
	private function _parse_wmv($tags) {

		$array = array();

		$array['mime']		= $tags['mime_type'];

		switch($array['mime']) {
			default:
			case 'video/x-ms-wmv':
				if(isset($tags['tags']['asf']['title']['0'])) {
					$array['title']		= $tags['tags']['asf']['title']['0'];
				}
				else {
					$array['title']		= urldecode($this->_pathinfo['filename']);
				}
				$array['video_codec']		= $tags['video']['streams']['2']['codec'];
				$array['audio_codec']		= $tags['audio']['streams']['1']['codec'];
				$array['resolution_x']		= $tags['video']['streams']['2']['resolution_x'];
				$array['resolution_y']		= $tags['video']['streams']['2']['resolution_y'];
				$array['comment']		= $tags['tags']['asf']['title']['1'];
			break;
		}

		return $array;

	} // _parse_wmv

	/**
	 * _parse_flv
	 * This attempts to parse our the information on an flv file and present it in some
	 * kind of sane format, this is a little hard as these files don't have tags
	 */
	private function _parse_flv($tags) {

		$array = array();

		$array['title']			= urldecode($this->_pathinfo['filename']);
		$array['video_codec']		= $tags['video']['codec'];
		$array['audio_codec']		= $tags['audio']['dataformat'];
		$array['resolution_x']		= $tags['video']['resolution_x'];
		$array['resolution_y']		= $tags['video']['resolution_y'];
		$array['mime']			= $tags['mime_type'];
		$array['comment']		= $tags['video']['codec'];

		return $array;

	} // _parse_flv

	/**
	 * _parse_filename
	 *
	 * This function uses the passed file and dir patterns
	 * To pull out extra tag information and populate it into
	 * its own array
	 *
	 * @param	string	$filename	Filename that want to parse 
	 * @return	array	Parsed results
	 */
	private function _parse_filename($filename) {

		$results = array();

		// Correctly detect the slash we need to use here
		if (strpos($filename, '/') !== false) {
			$slash_type = '/';
		}
		else {
			$slash_type = '\\';
		}

		$pattern = preg_quote($this->_dir_pattern) . $slash_type . preg_quote($this->_file_pattern);
		preg_match_all("/\%\w/",$pattern,$elements);

		$preg_pattern = preg_quote($pattern);
		$preg_pattern = preg_replace("/\%[Ty]/","([0-9]+?)",$preg_pattern);
		$preg_pattern = preg_replace("/\%\w/","(.+?)",$preg_pattern);
		$preg_pattern = str_replace("/","\/",$preg_pattern);
		$preg_pattern = str_replace(" ","\s",$preg_pattern);
		$preg_pattern = "/" . $preg_pattern . "\..+$/";
		preg_match($preg_pattern,$filename,$matches);
		/* Cut out the Full line, we don't need that */
		array_shift($matches);

		/* Foreach through what we've found */
		foreach ($matches as $key=>$value) {
			$new_key = translate_pattern_code($elements['0'][$key]);
			if ($new_key) {
				$results[$new_key] = $value;
			}
		} // end foreach matches

		$results['size'] = filesize($filename);

		return $results;

	} // _parse_filename

	/**
	 * _id3v2_tag_to_frame
	 *
	 * This translates the tag name to a frame, if there a many it returns the first
	 * one if finds that exists in the raw
	 *
	 * @param	string	$tag_name	Tag name
	 * @return	mixed	If found id3v2 frame, return frame. If not found, return false.
	 */
	private function _id3v2_tag_to_frame($tag_name) {

		static $map = array(
			'comment'=>array('COM','COMM'),
			'cd_ident'=>array('MCDI','MCI'),
			'album'=>array('TAL','TALB'),
			'language'=>array('TLA','TLAN'),
			'mood'=>array('TMOO'),
			'artist'=>array('TPE1'),
			'year'=>array('TDRC'));

		foreach ($map[$tag_name] as $frame) {
			if (isset($this->_raw['id3v2'][$frame])) {
				return $frame;
			}
		}

		return false;

	} // _id3v2_tag_to_frame

	/**
	 * _clean_tag
	 *
	 * This function cleans up the tag that it's passed using Iconv
	 * if we've got it. It also takes an optional encoding param
	 * for the cases where we know what the tags source encoding
	 * is, and or if it's different then the encoding recorded
	 * in the file
	 *
	 * @param	string	$tag	Encoding string
	 * @param	string	$encoding	Encode charset
	 * @return	string	Return encoded string
	 */
	private function _clean_tag($tag, $encoding = null) {

		// Default to getID3's native encoding
		if (!$encoding) {
			$encoding = $this->_getID3->encoding;
		}
		// If we've got iconv then go ahead and clear her up
		if (strcmp($encoding, $this->encoding) == 0) {
			debug_event('vainfo', "\$encoding -> ${encoding}, \$this->encoding -> {$this->encoding}", 5);
			return $tag;
		}
		if ($this->_iconv) {
			debug_event('vainfo', 'Use iconv()',5);

			// Try GNU iconv //TRANSLIT extension first
			$new_encoding = $this->encoding . '//TRANSLIT';
			$clean = iconv($encoding, $new_encoding, $tag);

			// If that fails, do a plain conversion
			if(strcmp($clean, '') == 0) {
				$clean = iconv($encoding, $this->encoding, $tag);
			}
		}
		elseif (function_exists('mb_convert_encoding')) {
			debug_event('vainfo', 'Use mbstring',5);
			debug_event('vainfo', "Try to convert from {$this->encoding} to $encoding", 5);
			$clean = mb_convert_encoding($tag, $encoding, $this->encoding);
		}
		else {
			$clean = $tag;
		}

		return $clean;

	} // _clean_tag

	/**
	 * set_broken
	 *
	 * This fills all tag types with Unknown (Broken)
	 *
	 * @return	array	Return broken title, album, artist
	 */
	public function set_broken() {

		/* Pull In the config option */
		$order = Config::get('tag_order');

		if (!is_array($order)) {
			$order = array($order);
		}

		$key = array_shift($order);

		$broken[$key]['title'] = '**BROKEN** ' . $this->filename;
		$broken[$key]['album'] = 'Unknown (Broken)';
		$broken[$key]['artist'] = 'Unknown (Broken)';

		return $broken;

	} // set_broken

} // end class vainfo
?>
