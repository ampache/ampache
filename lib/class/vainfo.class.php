<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

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
 * vainfo
 * This class takes the information pulled from getID3 and returns it in a
 * Ampache friendly way. 
 */
class vainfo { 

	/* Default Encoding */
	public $encoding = '';
	public $encoding_id3v1 = 'ISO-8859-1';
	public $encoding_id3v2 = 'ISO-8859-1';
	
	/* Loaded Variables */
	public $filename = '';
	public $type = '';
	public $tags = array();

	/* Internal Information */
	public $_raw 		= array();
	public $_raw2		= array();
	public $_getID3 	= '';
	public $_iconv		= false; 
	public $_file_encoding	= '';
	public $_file_pattern	= '';
	public $_dir_pattern	= '';

	/* Internal Private */
	private $_binary_parse	= array(); 
	private $_pathinfo; 
	private $_broken = false; 

	/**
	 * Constructor
	 * This function just sets up the class, it doesn't
	 * actually pull the information
	 */
	public function __construct($file,$encoding='',$encoding_id3v1='',$encoding_id3v2='',$dir_pattern,$file_pattern) { 

		/* Check for ICONV */
		if (function_exists('iconv')) { 
			$this->_iconv = true;
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

		// Before we roll the _getID3 route let's see about using exec + a binary
/*
		if (!isset($this->_binary_parse[$this->_pathinfo['extension']])) { 
			// Figure out if we've got binary parse ninja-skills here
			$this->_binary_parse[$this->_pathinfo['extension']] = $this->can_binary_parse(); 
			debug_event('BinaryParse','Binary Parse for ' . $this->_pathinfo['extension'] . ' set to ' . make_bool($this->_binary_parse[$this->_pathinfo['extension']]),'5'); 
		} 
*/
		// Initialize getID3 engine
		$this->_getID3 = new getID3();

//		if ($this->_binary_parse[$this->_pathinfo['extension']]) { return true; } 

		// get id3tag encodings
		// we have to run this right here because we don't know what we have in the files
		// and so we pull broken, then pull good later... this needs to be fixed
		try {
			$this->_raw2 = $this->_getID3->analyze($file);
		}
		catch (Exception $error) {
			debug_event('getid3',"Broken file $file " . $error->message,'1');
			$this->_broken = true; 
			return false; 
		}

		if(function_exists('mb_detect_encoding')) {
			$this->encoding_id3v1 = array();
			$this->encoding_id3v1[] = mb_detect_encoding($this->_raw2['tags']['id3v1']['artist']['0']);
			$this->encoding_id3v1[] = mb_detect_encoding($this->_raw2['tags']['id3v1']['album']['0']);
			$this->encoding_id3v1[] = mb_detect_encoding($this->_raw2['tags']['id3v1']['genre']['0']);
			$this->encoding_id3v1[] = mb_detect_encoding($this->_raw2['tags']['id3v1']['title']['0']);
			array_multisort($this->encoding_id3v1);
			array_splice($this->encoding_id3v1, -4, 3);
			if($this->encoding_id3v1[0] != "ASCII") {
				$this->encoding_id3v1 = $this->encoding_id3v1[0];
			} else {
				$this->encoding_id3v1 = "ISO-8859-1";
			}
			

			$this->encoding_id3v2 = array();
			$this->encoding_id3v2[] = mb_detect_encoding($this->_raw2['tags']['id3v2']['artist']['0']);
			$this->encoding_id3v2[] = mb_detect_encoding($this->_raw2['tags']['id3v2']['album']['0']);
			$this->encoding_id3v2[] = mb_detect_encoding($this->_raw2['tags']['id3v2']['genre']['0']);
			$this->encoding_id3v2[] = mb_detect_encoding($this->_raw2['tags']['id3v2']['title']['0']);
			array_multisort($this->encoding_id3v2);
			array_splice($this->encoding_id3v2, -4, 3);
			if($this->encoding_id3v2[0] != "ASCII"){
				$this->encoding_id3v2 = $this->encoding_id3v2[0];
			} else {
				$this->encoding_id3v2 = "ISO-8859-1";
			}
		}
		else {
			$this->encoding_id3v1 = "ISO-8859-1";
			$this->encoding_id3v2 = "ISO-8859-1";
		}

		$this->_getID3->option_md5_data			= false;
		$this->_getID3->option_md5_data_source	= false;
		$this->_getID3->option_tags_html		= false;
		$this->_getID3->option_extra_info		= true;
		$this->_getID3->option_tag_lyrics3		= true;
		$this->_getID3->encoding				= $this->encoding; 
		$this->_getID3->encoding_id3v1			= $this->encoding_id3v1;
		$this->_getID3->encoding_id3v2			= $this->encoding_id3v2;
		$this->_getID3->option_tags_process		= true; 


	} // vainfo


	/**
	 * get_info
	 * This function takes a filename and returns the $_info array
	 * all filled up with tagie goodness or if specified filename
	 * pattern goodness
	 */
	public function get_info() {

                // If this is broken, don't waste time figuring it out a second time, just return 
                // their rotting carcass of a media file back on the pile
                if ($this->_broken) {
                        $this->tags = $this->set_broken();
                        return true;
                }

		// If we've got a green light try out the binary
//		if ($this->_binary_parse[$this->_pathinfo['extension']]) { 
//			$this->run_binary_parse(); 	
//		} 
		
//		else { 

			/* Get the Raw file information */
			try { 
				$this->_raw = $this->_getID3->analyze($this->filename);
			} 
			catch (Exception $error) { 
				debug_event('getid3',$error->message,'1');
			} 

			/* Figure out what type of file we are dealing with */
			$this->type = $this->_get_type();

			/* Get the general information about this file */
			$info = $this->_get_info();
//		} 

		/* Gets the Tags */
		$this->tags = $this->_get_tags();
		$this->tags['info'] = $info;

		unset($this->_raw);
	
	} // get_info

	/**
	 * get_tag_type
	 * This takes the result set, and the the tag_order
	 * As defined by your config file and trys to figure out
	 * which tag type it should use, if your tag_order
	 * doesn't match anything then it just takes the first one
	 * it finds in the results. 
	 */
	public static function get_tag_type($results) {

		/* Pull In the config option */
		$order = Config::get('tag_order');

		if (!is_array($order)) {
			$order = array($order);
		}
	
		/* Foreach through the defined key order
		 * the first one we find is the first one we use 
		 */
		foreach($order as $key) {
			if ($results[$key]) {
				$returned_key = $key;
				break;
			}
		}

		/* If we didn't find anything then default it to the
		 * first in the results set
		 */
		if (!isset($returned_key)) {
			$keys = array_keys($results);
			$returned_key = $keys['0'];
		}

		return $returned_key;

	} // get_tag_type

	/**
	 * clean_tag_info
	 * This function takes the array from vainfo along with the 
	 * key we've decided on and the filename and returns it in a 
	 * sanatized format that ampache can actually use
	 */
	public static function clean_tag_info($results,$key,$filename) {

		$info = array();

		$clean_array = array("\n","\t","\r","\0");
		$wipe_array  = array("","","","");

		$info['file']		= $filename;
		$info['title']		= stripslashes(trim($results[$key]['title']));
		$info['comment']	= Dba::escape(str_replace($clean_array,$wipe_array,$results[$key]['comment']));

		/* This are pulled from the info array */
		$info['bitrate']	= intval($results['info']['bitrate']);
		$info['rate']		= intval($results['info']['sample_rate']);
		$info['mode']		= $results['info']['bitrate_mode'];

		// Convert special version of constant bitrate mode to cbr
		if($info['mode'] == 'con') {
			$info['mode'] = 'cbr';
		}

		$info['size']			= $results['info']['filesize'];
		$info['mime']			= $results['info']['mime'];
		$into['encoding']		= $results['info']['encoding'];
		$info['time']			= intval($results['info']['playing_time']);
		$info['channels']		= intval($results['info']['channels']);

		// Specific Audio Flags
		if (!$results[$key]['video_codec']) { 
			$slash_point = strpos($results[$key]['disk'],'/'); 
			if ($slash_point !== FALSE) { 
				$results[$key]['disk'] = substr($results[$key]['disk'],0,$slash_point); 
			} 
			/* These are used to generate the correct ID's later */
			$info['year']		= intval($results[$key]['year']);
			$info['disk']		= intval($results[$key]['disk']);
			$info['artist']		= trim($results[$key]['artist']);
			$info['album']		= trim($results[$key]['album']);
			$info['genre']		= trim($results[$key]['genre']);
			/* @TODO language doesn't import from id3tag. @momo-i */
			$info['language']	= Dba::escape($results[$key]['language']);
			if (!empty($results[$key]['unsynchronised lyric'])) { // ID3v2 USLT
				$info['lyrics']	= str_replace(array("\r\n","\r","\n"), '<br />',strip_tags($results[$key]['unsynchronised lyric']));
			}
			else { // Lyrics3 v2.0
				$info['lyrics']	= str_replace(array("\r\n","\r","\n"), '<br />',strip_tags($results['info']['lyrics']['unsynchedlyrics']));
			}
			$info['track']		= intval($results[$key]['track']);
		}
		else { 
			$info['resolution_x']	= intval($results[$key]['resolution_x']); 
			$info['resolution_y']	= intval($results[$key]['resolution_y']); 
			$info['audio_codec']	= Dba::escape($results[$key]['audio_codec']); 
			$info['video_codec']	= Dba::escape($results[$key]['video_codec']); 
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

		/* Gather Tag information from the filenames */
		$results['file']	= $this->_parse_filename($this->filename);

		/* Return false if we don't have 
		 * any tags to look at 
		 */
		if (!is_array($this->_raw['tags'])) { 
			return $results; 
		}

		/* The tags can come in many different shapes and colors 
		 * depending on the encoding time of day and phase of the
		 * moon
		 */
		foreach ($this->_raw['tags'] as $key=>$tag_array) { 
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
					$results[$key] = $this->_parse_flv($this->_raw2); 
				break; 
				case 'mpg':
				case 'mpeg': 
					debug_event('_get_tags', 'Parsing MPEG', '5');
					$results[$key] = $this->_parse_mpg($this->_raw2);
				break;
				case 'asf':
				case 'wmv':
					debug_event('_get_tags', 'Parsing WMV/WMA/ASF', '5');
					$results[$key] = $this->_parse_wmv($this->_raw2);
				break;
				case 'avi': 
					debug_event('_get_tags', 'Parsing avi', '5');
					$results[$key] = $this->_parse_avi($this->_raw2); 
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


		return $results;

	} // _get_tags

	/**
	 * _get_info
	 * This function gathers and returns the general information
	 * about a song, vbr/cbr sample rate channels etc
	 */
	private function _get_info() { 

		$array = array();

		/* Try to pull the information directly from
		 * the audio array 
		 */
		if ($this->_raw['audio']['bitrate_mode']) { 
			$array['bitrate_mode'] 	= $this->_raw['audio']['bitrate_mode'];
		}
		if ($this->_raw['audio']['bitrate']) { 
			$array['bitrate']	= $this->_raw['audio']['bitrate'];
		}
		if ($this->_raw['audio']['channels']) { 
			$array['channels'] 	= intval($this->_raw['audio']['channels']);
		}
		if ($this->_raw['audio']['sample_rate']) { 
			$array['sample_rate']	= intval($this->_raw['audio']['sample_rate']);
		}
		if ($this->_raw['filesize']) { 
			$array['filesize']	= intval($this->_raw['filesize']);
		}
		if ($this->_raw['encoding']) { 
			$array['encoding']	= $this->_raw['encoding'];
		}
		if ($this->_raw['mime_type']) { 
			$array['mime']		= $this->_raw['mime_type'];
		}
		if ($this->_raw['playtime_seconds']) { 
			$array['playing_time']	= $this->_raw['playtime_seconds'];
		}
		if ($this->_raw['lyrics3']) {
			$array['lyrics'] = $this->_raw['lyrics3'];
		}

		return $array;
	
	} // _get_info

	/**
	 * _clean_type
	 * This standardizes the type that we are given into a reconized 
	 * type
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
		foreach ($tags as $tag=>$data) {

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
				case 'tracknumber':
					$array['track']	= $this->_clean_tag($data['0']);
				break;
				case 'discnumber': 
					$array['disk'] 	= $this->_clean_tag($data['0']); 
				break;
				case 'date':
					$array['year']	= $this->_clean_tag($data['0']);
				break;
			} // end switch

			$array[$tag] = $this->_clean_tag($data['0']);

		} // end foreach

		return $array;

	} // _parse_vorbiscomment

	/**
	 * _parse_id3v1
	 * This function takes a id3v1 tag set from getid3() and then
	 * returns the elements translated using iconv if needed in a 
	 * pretty little format
	 */
	private function _parse_id3v1($tags) { 

		$array = array();

		$encoding = $this->_raw['id3v1']['encoding'];
		
		/* Go through all the tags */
		foreach ($tags as $tag=>$data) { 

			/* This is our baseline for naming 
			 * so no translation needed 
			 */
			$array[$tag]	= $this->_clean_tag($data['0'],$encoding);
			
		} // end foreach

		return $array;

	} // _parse_id3v1

	/**
	 * _parse_id3v2
	 * This function takes a id3v2 tag set from getid3() and then
	 * returns the lelements translated using iconv if needed in a
	 * pretty little format
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
				case 'pos':
					$el = split('/', $data['0']);
					$array['disk'] = $el[0];
				break;
				case 'track_number':
					$array['track'] = $this->_clean_tag($data['0'],'');
				break;	
				break;
				case 'comments':
					$array['comment'] = $this->_clean_tag($data['0'],'');
				break;
				case 'title': 
					$array['title'] = $this->_clean_tag($data['0'],''); 
				break; 
				default: 
					$array[$tag]	= $this->_clean_tag($data['0'],'');
				break;
			} // end switch on tag
		
		} // end foreach
		
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
			
			$array[$tag] = $this->_clean_tag($data['0'],$this->_file_encoding);

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
					$array['album'] = $this->_clean_tag($data['0'],$this->_file_encoding); 
				break;
				default: 
					$array[$tag] = $this->_clean_tag($data['0'],$this->_file_encoding); 
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
		if (strpos($this->_raw2['mime_type'],'video') !== false) { 
			$info = $this->_parse_avi($this->_raw2); 
			$info['video_codec'] = $this->_raw2['quicktime']['ftyp']['fourcc']; 
			$array = array_merge($info,$array); 
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
	 * This function uses the passed file and dir patterns
	 * To pull out extra tag information and populate it into 
	 * it's own array
	 */
	private function _parse_filename($filename) { 

		$results = array();

		// Correctly detect the slash we need to use here
		if (strstr($filename,"/")) {
			$slash_type = '/';
		}
		else {
			$slash_type = '\\';
		}

		$pattern = preg_quote($this->_dir_pattern) . $slash_type . preg_quote($this->_file_pattern); 
		preg_match_all("/\%\w/",$pattern,$elements);
		
		$preg_pattern = preg_quote($pattern);
		$preg_pattern = preg_replace("/\%\w/","(.+)",$preg_pattern);
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

		return $results;

	} // _parse_filename

	/**
	 * _id3v2_tag_to_frame
	 * This translates the tag name to a frame, if there a many it returns the first
	 * one if finds that exists in the raw
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
	 * This function cleans up the tag that it's passed using Iconv
	 * if we've got it. It also takes an optional encoding param
	 * for the cases where we know what the tags source encoding
	 * is, and or if it's different then the encoding recorded
	 * in the file
	 */
	private function _clean_tag($tag,$encoding='') { 

		// If we've got iconv then go ahead and clear her up		
		if ($this->_iconv) { 
			/* Guess that it's UTF-8 */
			/* Try GNU iconv //TRANSLIT extension first */
			if (!$encoding) { $encoding = $this->_getID3->encoding; }
			$charset = $this->encoding . '//TRANSLIT';
			$enc_tag = iconv($encoding,$charset,$tag);
			if(strcmp($enc_tag, "") == 0) {
				$enc_tag = iconv($encoding,$this->encoding,$tag);
			}
		}

		return $enc_tag;

	} // _clean_tag

	/**
	 * can_binary_parse
	 * This returns true/false if we can do a binary parse of the file in question
	 * only the extension is passed so this can be inaccurate
	 */
	public function can_binary_parse() { 

		// We're going to need exec for this
		if (!is_callable('exec')) { 
			return false; 
		} 


		// For now I'm going to use an approved list of apps, later we should allow user config
		switch ($this->_pathinfo['extension']) { 
			case 'mp3': 			
				// Verify the application is there and callable
				exec('id3v2 -v',$results,$retval); 
				if ($retval == 0) { return true; } 
			break; 
			default:
				//FAILURE
			break; 
		}

		return false; 

	} // can_binary_parse

	/**
	 * run_binary_parse
	 * This runs the binary parse operations here down in Ampache land
	 * it is passed the filename, and only called if can_binary_parse passes
	 */
	public function run_binary_parse() { 

		// Switch on the extension
		switch ($this->_pathinfo['extension']) { 
			case 'mp3': 
				$this->_raw['tags'] = $this->mp3_binary_parse();
			break; 
			default:
				$this->_raw['tags'] = array(); 
			break; 
		} // switch on extension

	} // run_binary_parse

	/**
	 * mp3_binary_parse
	 * This tries to read the tag information from mp3s using a binary and the exec() command
	 * This will not work on a lot of systems... but it should be faster
	 */
	public function mp3_binary_parse() { 

		require_once(Config::get('prefix') . '/modules/getid3/module.tag.id3v2.php'); 

		$filename = escapeshellarg($this->filename); 

		exec('id3v2 -l ' . $filename,$info,$retval); 

		if ($retval != 0) { return array(); } 

		$position=0; 
		$results = array(); 

		// If we've got Id3v1 tag information
		if (substr($info[$position],0,5) == 'id3v1') { 
			$position++; 
			$v1['title'][]	= trim(substr($info[$position],8,30)); 
			$v1['artist'][]	= trim(substr($info[$position],49,79));
			$position++; 
			$v1['album'][]	= trim(substr($info[$position],8,30)); 
			$v1['year'][]	= trim(substr($info[$position],47,53)); 
			$v1['genre'][]	= trim(preg_replace("/\(\d+\)/","",substr($info[$position],60,strlen($info[$position])))); 
			$position++; 
			$v1['comment'][]= trim(substr($info[$position],8,30)); 
			$v1['track'][]	= trim(substr($info[$position],48,3)); 
			$results['id3v1'] = $v1; 
			$position++; 
		}
		if (substr($info[$position],0,5) == 'id3v2') { 
			$position++; 
			$element_count = count($info);
			while ($position < $element_count) { 
				$position++;
				$element = getid3_id3v2::FrameNameShortLookup(substr($info[$position],0,4));
				if (!$element) { continue; } 
				$data = explode(":",$info[$position],2); 
				$value = array_pop($data); 
				$results['id3v2'][$element][] = $value; 
			} 

		} // end if id3v2
		return $results; 

	} // mp3_binary_parse

        /**
         * set_broken
         * This fills all tag types with Unknown (Broken) 
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
