<?php

// +----------------------------------------------------------------------+
// | PHP version 4.1.0                                                    |
// +----------------------------------------------------------------------+
// | Placed in public domain by Allan Hansen, 2002. Share and enjoy!      |
// +----------------------------------------------------------------------+
// | /demo/demo.audioinfo.class.php                                       |
// |                                                                      |
// | Example wrapper class to extract information from audio files        |
// | through getID3().                                                    |
// |                                                                      |
// | getID3() returns a lot of information. Much of this information is   |
// | not needed for the end-application. It is also possible that some    |
// | users want to extract specific info. Modifying getID3() files is a   |
// | bad idea, as modifications needs to be done to future versions of    |
// | getID3().                                                            |
// |                                                                      |
// | Modify this wrapper class instead. This example extracts certain     |
// | fields only and adds a new root value - encoder_options if possible. |
// | It also checks for mp3 files with wave headers.                      |
// +----------------------------------------------------------------------+
// | Example code:                                                        |
// |   $au = new AudioInfo();                                             |
// |   print_r($au->Info('file.flac');                                    |
// +----------------------------------------------------------------------+
// | Authors: Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
//



/**
* getID3() settings
*/

require_once(conf('prefix') . "/modules/id3/getid3/getid3.php");




/**
* Class for extracting information from audio files with getID3().
*/

class AudioInfo {

	/**
	* Private variables
	*/
	var $result = NULL;
	var $info   = NULL;




	/**
	* Constructor
	*/

	function AudioInfo() {

		// Initialize getID3 engine
		$this->getID3 = new getID3;
		$this->getID3->option_md5_data        	= false;
		$this->getID3->option_md5_data_source 	= false;
		$this->getID3->encoding			= 'UTF-8';
	}




	/**
	* Extract information - only public function
	*
	* @access   public
	* @param    string  file    Audio file to extract info from.
	*/

	function Info($file) {

		// Analyze file
		$this->info = $this->getID3->analyze($file);

		// Exit here on error
		if (isset($this->info['error'])) {
			return array ('error' => $this->info['error']);
		}

		// Init wrapper object
		$this->result = array ();
		$this->result['format_name']     = @$this->info['fileformat'].'/'.@$this->info['audio']['dataformat'].(isset($this->info['video']['dataformat']) ? '/'.@$this->info['video']['dataformat'] : '');
		$this->result['encoder_version'] = @$this->info['audio']['encoder'];
		$this->result['encoder_options'] = NULL;
		$this->result['bitrate_mode']    = @$this->info['audio']['bitrate_mode'];
		$this->result['channels']        = @$this->info['audio']['channels'];
		$this->result['sample_rate']     = @$this->info['audio']['sample_rate'];
		$this->result['bits_per_sample'] = @$this->info['audio']['bits_per_sample'];
		$this->result['playing_time']    = @$this->info['playtime_seconds'];
		$this->result['avg_bit_rate']    = @$this->info['audio']['bitrate'];
		$this->result['tags']            = @$this->info['tags'];
		$this->result['comments']        = @$this->info['comments'];
		$this->result['warning']         = @$this->info['warning'];
		$this->result['md5']             = @$this->info['md5_data'];
		//$this->result['full']		 = @$this->info;

        // The vollmer way
        if($this->info['fileformat'] === 'mp3' || $this->info['audio']['dataformat'] === "mp3")
    	{
	    if (isset($this->info['tags']['id3v1'])) { $this->info['id3v1']['comments'] = $this->info['tags']['id3v1']; }
	    if (isset($this->info['tags']['id3v2'])) { $this->info['id3v2']['comments'] = $this->info['tags']['id3v2']; }

            if ($this->info['id3v1']) {
	    	if (function_exists('iconv')) { 
	                $this->result['id3v1']['title']		= iconv("UTF-8", "ISO-8859-1", $this->info['id3v1']['comments']['title'][0]);
			$this->result['id3v1']['artist']	= iconv("UTF-8", "ISO-8859-1", $this->info['id3v1']['comments']['artist'][0]);
			$this->result['id3v1']['album']		= iconv("UTF-8", "ISO-8859-1", $this->info['id3v1']['comments']['album'][0]);
			$this->result['id3v1']['comment']	= iconv("UTF-8", "ISO-8859-1", $this->info['id3v1']['comments']['comment'][0]);
			$this->result['id3v1']['genre']		= iconv("UTF-8", "ISO-8859-1", $this->info['id3v1']['comments']['genre'][0]);
		}
		else {
			$this->result['id3v1']['title']		= $this->info['id3v1']['comments']['title'][0];
			$this->result['id3v1']['artist']	= $this->info['id3v1']['comments']['artist'][0];
			$this->result['id3v1']['album']		= $this->info['id3v1']['comments']['album'][0];
			$this->result['id3v1']['comment']	= $this->info['id3v1']['comments']['comment'][0];	
			$this->result['id3v1']['genre']		= $this->info['id3v1']['comments']['genre'][0];
		} // no iconv
		$this->result['id3v1']['year']	= $this->info['id3v1']['comments']['year'][0];
		$this->result['id3v1']['track']	= $this->info['id3v1']['comments']['track'][0];

            }
            if ($this->info['id3v2']) {
	    	if (function_exists('iconv')) { 
			$this->result['id3v2']['title']     = iconv("UTF-8", "ISO-8859-1", $this->info['id3v2']['comments']['title'][0]);
			$this->result['id3v2']['artist']    = iconv("UTF-8", "ISO-8859-1", $this->info['id3v2']['comments']['artist'][0]);
			$this->result['id3v2']['album']     = iconv("UTF-8", "ISO-8859-1", $this->info['id3v2']['comments']['album'][0]);
			$this->result['id3v2']['comment']   = iconv("UTF-8", "ISO-8859-1", $this->info['id3v2']['comments']['comment'][0]);
		}
		else {
			$this->result['id3v2']['title']	    = $this->info['id3v2']['comments']['title'][0];
                        $this->result['id3v2']['artist']    = $this->info['id3v2']['comments']['artist'][0];
                        $this->result['id3v2']['album']     = $this->info['id3v2']['comments']['album'][0];
                        $this->result['id3v2']['comment']   = $this->info['id3v2']['comments']['comment'][0];

		}
		$this->result['id3v2']['year']      = $this->info['id3v2']['comments']['year'][0];
		$this->result['id3v2']['genre']     = $this->info['id3v2']['comments']['genre'][0];
		$this->result['id3v2']['track']     = $this->info['id3v2']['comments']['track'][0];
		$this->result['id3v2']['genreid']   = $this->info['id3v2']['comments']['genreid'][0];
            }
        }
        elseif($this->info['fileformat'] === 'ogg') {
		if (function_exists('iconv')) { 
			$this->result['ogg']['title']   = iconv("UTF-8",conf('site_charset') . "//TRANSLIT", $this->info['ogg']['comments']['title'][0]);
			$this->result['ogg']['artist']  = iconv("UTF-8",conf('site_charset') . "//TRANSLIT", $this->info['ogg']['comments']['artist'][0]);
			$this->result['ogg']['album']   = iconv("UTF-8",conf('site_charset') . "//TRANSLIT", $this->info['ogg']['comments']['album'][0]);
			$this->result['ogg']['author']	= iconv("UTF-8",conf('site_charset') . "//TRANSLIT", $this->info['ogg']['comments']['author'][0]);
			$this->result['ogg']['genre'] = iconv("UTF-8",conf('site_charset') . "//TRANSLIT", $this->info['ogg']['comments']['genre'][0]); 
		}
		else {
			$this->result['ogg']['title'] 	= $this->info['ogg']['comments']['title'][0];
		        $this->result['ogg']['artist'] 	= $this->info['ogg']['comments']['artist'][0];
		        $this->result['ogg']['album'] 	= $this->info['ogg']['comments']['album'][0];
			$this->result['ogg']['author']	= $this->info['ogg']['comments']['author'][0];
			$this->result['ogg']['genre'] = $this->info['ogg']['comments']['genre'][0]; 
		}

		$this->result['ogg']['year'] 	= $this->info['ogg']['comments']['date'][0];
		$this->result['ogg']['track'] 	= $this->info['ogg']['comments']['tracknumber'][0];
		
	} // if ogg
	/* If it's a WMA */
	elseif($this->info['fileformat'] === 'asf') {
		if (function_exists('iconv')) { 	
			$this->result['asf']['title'] 	= iconv("UTF-8","ISO-8859-1", $this->info['tags']['asf']['title'][0]);
			$this->result['asf']['artist']	= iconv("UTF-8","ISO-8859-1", $this->info['tags']['asf']['artist'][0]);
			$this->result['asf']['album']	= iconv("UTF-8","ISO-8859-1", $this->info['tags']['asf']['album'][0]);
			$this->result['asf']['comment']	= iconv("UTF-8","ISO-8859-1", $this->info['tags']['asf']['comment'][0]);
		} // if iconv
		else { 
			$this->result['asf']['title'] 	= $this->info['tags']['asf']['title'][0];
			$this->result['asf']['artist']	= $this->info['tags']['asf']['artist'][0];
			$this->result['asf']['album']	= $this->info['tags']['asf']['album'][0];
			$this->result['asf']['comment']	= $this->info['tags']['asf']['comment'][0];
		}
		$this->result['asf']['track']	= $this->info['tags']['asf']['track'][0];
		$this->result['asf']['year']	= $this->info['tags']['asf']['year'][0];
	} // if wma	
	/* If it's a flac */
	elseif($this->info['fileformat'] === 'flac') { 
		if (function_exists('iconv')) { 
			$this->result['flac']['title']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['vorbiscomment']['title'][0]);
			$this->result['flac']['artist'] 	= iconv("UTF-8","ISO-8859-1", $this->info['tags']['vorbiscomment']['artist'][0]);
			$this->result['flac']['album']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['vorbiscomment']['album'][0]);
			$this->result['flac']['comment']	= iconv("UTF-8","ISO-8859-1", $this->info['comments'][0]);
		}
		else {
			$this->result['flac']['title']		= $this->info['tags']['vorbiscomment']['title'][0];
			$this->result['flac']['artist']		= $this->info['tags']['vorbiscomment']['artist'][0];
			$this->result['flac']['album']		= $this->info['tags']['vorbiscomment']['album'][0];
			$this->result['flac']['comment']	= $this->info['comments'][0];
		}
		$this->result['flac']['track']	= $this->info['tags']['vorbiscomment']['tracknumber'][0];
		$this->result['flac']['year']	= $this->info['tags']['vorbiscomment']['date'][0];
		$this->result['flac']['genre']	= $this->info['tags']['vorbiscomment']['genre'][0];
	} // if flac

	elseif($this->info['fileformat'] === 'mp4') { 

		if (function_exists('iconv')) { 
			$this->result['m4a']['title']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['quicktime']['title'][0]);
			$this->result['m4a']['artist']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['quicktime']['artist'][0]);
			$this->result['m4a']['album']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['quicktime']['album'][0]);
			$this->result['m4a']['comment']		= iconv("UTF-8","ISO-8859-1", $this->info['tags']['quicktime']['comment'][0]);
		}
		else {
			$this->result['m4a']['title']		= $this->info['tags']['quicktime']['title'][0];
			$this->result['m4a']['artist']		= $this->info['tags']['quicktime']['artist'][0];
			$this->result['m4a']['album']		= $this->info['tags']['quicktime']['album'][0];
			$this->result['m4a']['comment']		= $this->info['tags']['quicktime']['comment'][0];
		}
		
		$this->result['m4a']['year']	= $this->info['tags']['quicktime']['creation_date'][0];


	} // if m4a 


        elseif($this->info['fileformat'] === 'mpc') {

                if (function_exists('iconv')) {
                        $this->result['mpc']['title']           = iconv("UTF-8","ISO-8859-1", $this->info['tags']['ape']['title'][0]);
                        $this->result['mpc']['artist']          = iconv("UTF-8","ISO-8859-1", $this->info['tags']['ape']['artist'][0]);
                        $this->result['mpc']['album']           = iconv("UTF-8","ISO-8859-1", $this->info['tags']['ape']['album'][0]);
                        $this->result['mpc']['comment']         = iconv("UTF-8","ISO-8859-1", $this->info['tags']['ape']['comment'][0]);
                }
                else {
                        $this->result['mpc']['title']           = $this->info['tags']['ape']['title'][0];
                        $this->result['mpc']['artist']          = $this->info['tags']['ape']['artist'][0];
                        $this->result['mpc']['album']           = $this->info['tags']['ape']['album'][0];
                        $this->result['mpc']['comment']         = $this->info['tags']['ape']['comment'][0];
                }

                $this->result['mpc']['year']    = $this->info['tags']['ape']['year'][0];
		$this->result['mpc']['track']	= $this->info['tags']['ape']['track'][0];
		$this->result['mpc']['genre']	= $this->info['tags']['ape']['genre'][0];

        } // if mpc


		// Post getID3() data handling based on file format
		$method = @$this->info['fileformat'].'Info';
		if (@$this->info['fileformat'] && method_exists($this, $method)) {
			$this->$method();
		}

		return $this->result;
	}





	/**
	* post-getID3() data handling for AAC files.
	*
	* @access   private
	*/

	function aacInfo() {
		$this->result['format_name']     = 'AAC';
	}




	/**
	* post-getID3() data handling for Wave files.
	*
	* @access   private
	*/

	function riffInfo() {
		if ($this->info['audio']['dataformat'] == 'wav') {

			$this->result['format_name'] = 'Wave';

		} else if (ereg('^mp[1-3]$', $this->info['audio']['dataformat'])) {

			$this->result['format_name'] = strtoupper($this->info['audio']['dataformat']);

		} else {

			$this->result['format_name'] = 'riff/'.$this->info['audio']['dataformat'];

		}
	}




	/**
	* * post-getID3() data handling for FLAC files.
	*
	* @access   private
	*/

	function flacInfo() {
		$this->result['format_name']     = 'FLAC';
	}





	/**
	* post-getID3() data handling for Monkey's Audio files.
	*
	* @access   private
	*/

	function macInfo() {
		$this->result['format_name']     = 'Monkey\'s Audio';
	}





	/**
	* post-getID3() data handling for Lossless Audio files.
	*
	* @access   private
	*/

	function laInfo() {
		$this->result['format_name']     = 'La';
	}





	/**
	* post-getID3() data handling for Ogg Vorbis files.
	*
	* @access   private
	*/

	function oggInfo() {
		if ($this->info['audio']['dataformat'] == 'vorbis') {

			$this->result['format_name']     = 'Ogg Vorbis';

		} else if ($this->info['audio']['dataformat'] == 'flac') {

			$this->result['format_name'] = 'Ogg FLAC';

		} else if ($this->info['audio']['dataformat'] == 'speex') {

			$this->result['format_name'] = 'Ogg Speex';

		} else {

			$this->result['format_name'] = 'Ogg '.$this->info['audio']['dataformat'];

		}
	}




	/**
	* post-getID3() data handling for Musepack files.
	*
	* @access   private
	*/

	function mpcInfo() {
		$this->result['format_name']     = 'Musepack';
	}




	/**
	* post-getID3() data handling for MPEG files.
	*
	* @access   private
	*/

	function mp3Info() {
		$this->result['format_name']     = 'MP3';
	}




	/**
	* post-getID3() data handling for MPEG files.
	*
	* @access   private
	*/

	function mp2Info() {
		$this->result['format_name']     = 'MP2';
	}





	/**
	* post-getID3() data handling for MPEG files.
	*
	* @access   private
	*/

	function mp1Info() {
		$this->result['format_name']     = 'MP1';
	}




	/**
	* post-getID3() data handling for WMA files.
	*
	* @access   private
	*/

	function asfInfo() {
		$this->result['format_name']     = strtoupper($this->info['audio']['dataformat']);
	}



	/**
	* post-getID3() data handling for Real files.
	*
	* @access   private
	*/

	function realInfo() {
		$this->result['format_name']     = 'Real';
	}





	/**
	* post-getID3() data handling for VQF files.
	*
	* @access   private
	*/

	function vqfInfo() {
		$this->result['format_name']     = 'VQF';
	}

}


?>
