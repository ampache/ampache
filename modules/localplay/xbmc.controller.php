<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * AmpacheXBMC Class
 * the Ampache XBMC Controller, this is the glue between
 * the XBMC HTTPapi and the Ampahce Localplay class
 */
class AmpacheXBMC {

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	function AmpacheXBMC() { 

	} // AmpacheXBMC


	/**
	 * function_map
	 * This function returns a named array of the functions
	 * that this player supports and their names in this local
	 * class. This is a REQUIRED function
	 */
	function function_map() { 

                $map = array();

		/* Required Functions */
         $map['add']            = 'add_songs';
         $map['delete']         = 'delete_songs';
         $map['play']           = 'play';
         $map['stop']           = 'stop';
         $map['get']            = 'get_songs';
         $map['status']			= 'get_status';
         $map['connect']        = 'connect';
		 
		
		/* Recommended Functions */
		$map['next']			= 'next';
		$map['prev']			= 'prev';
		$map['pause']			= 'pause';
		$map['volume_set']      = 'volume_set';
	
		/* Optional Functions */
		$map['delete_all']   	= 'clear_playlist';
		$map['shutdown']    	= 'xbmc_shutdown';

        return $map;

	} // function_map
	
	//
	function aXBMCCmd($cmd, $param=''){
	
		if($param == ''){
			$fs = fopen("http://" . conf('localplay_xbmc_hostname') . "/xbmcCmds/xbmcHttp?command=" . $cmd,"r");
		}else{
			$fs = fopen("http://" . conf('localplay_xbmc_hostname') . "/xbmcCmds/xbmcHttp?command=" . $cmd . "&parameter=" . $param,"r");
		}
		
		if($fs){
			stream_set_timeout($fs,2);
			$ret = "";	
			while (!feof($fs)) {
			   $ret = $ret . fgets($fs, 128);
			}
			fclose($fs);
			
			$ret = strip_tags($ret,"<li>");
		   
			$aret = explode("<li>",$ret);
			$aret = array_slice($aret,1);
			return $aret;
		} 
		
		return "";
		
	} //aXBMCCmd
	
	function XBMCCmd($cmd, $param=''){
	
		
		if($param == ''){
			$fs = fopen("http://" . conf('localplay_xbmc_hostname') . "/xbmcCmds/xbmcHttp?command=" . $cmd,"r");
		}else{
			$fs = fopen("http://" . conf('localplay_xbmc_hostname') . "/xbmcCmds/xbmcHttp?command=" . $cmd . "&parameter=" . $param,"r");
		}
		if($fs){
			stream_set_timeout($fs,1);
			$ret = "";	
			while (!feof($fs)) {
				$ret = $ret . fgets($fs, 128);
			}
			fclose($fs);
			
			$ret = strip_tags($ret);
			return trim($ret);
		}
		return "";

	}//XBMCCmd
	//

	/**
	 * preference
	 * This function returns an array of the preferences and their 
	 * information for Ampache to use All preferences will get a 
	 * localplay_xbmc_ appended to their name to avoid conflicts
	 * however this controller does not need to take that into acount
	 * REQUIRE for Locaplay
	 */
	function preferences() { 

		$preferences = array(); 

		$preferences[] = array('name'=>'hostname','default'=>'xbox','type'=>'string','description'=>'XBOX Hostname');
		$preferences[] = array('name'=>'smbpath','default'=>'smb://hostname/mp3/','type'=>'string','description'=>'Samba share path to mp3s');
		
		//needed to add basic authentication support later
		//$preferences[] = array('name'=>'username','default'=>'xbox','type'=>'string','description'=>'XBMC Username');
		//$preferences[] = array('name'=>'password','default'=>'','type'=>'string','description'=>'XBMC Password');

		return $preferences;

	} // preferences


	/**
	 * add_songs
	 * This must take an array of URL's from Ampache
	 * and then add them to XBMC
	 */
	function add_songs($songs) { 
	
		//set playlist to music, playlist 0
		$ret = $this->XBMCCmd("SetCurrentPlaylist","0");
		
		if ($ret != "OK") { 
			debug_event('xbmc_add','Error: Unable to set playlist on xbmc ' . $ret,'1');
		}


		foreach ($songs as $song_id) { 
			$song = new Song($song_id);
			
			//print($song->get_rel_path());
			
			$url = conf('localplay_xbmc_smbpath') . $song->get_rel_path();
			
			//add song to playlist 0, note the ;0 after the url...
			$ret = $this->XBMCCmd("AddToPlayList",urlencode($url . ";0"));
			//print(urlencode($url).";0");
			
			if ($ret != "OK") { 
				debug_event('xbmc_add','Error: Unable to add $url to xbmc ' . $ret,'1');
			}

		} // end foreach

		return true;

	} // add_songs


	/**
	 * delete_songs
	 * This must take an array of ID's (as passed by get function) from Ampache
	 * and delete them from MPD
	 */
	function delete_songs($songs) { 

		/* Default to true */
		$return = true;

		/* This should be an array of UID's as returned by
		 * the get function so that we can just call the class based 
		 * functions to remove them or if there isn't a uid for 
		 * the songs, then however ya'll have stored them
		 * in this controller 
		 */
		 
		 //RemoveFromPlaylist
		foreach ($songs as $song_id) { 

			$song = new Song($song_id);
			
			$url = conf('localplay_xbmc_smbpath') . $song->get_rel_path();
			
			$ret = $this->XBMCCmd("RemoveFromPlaylist",urlencode($url . ";0"));
			
			if ($ret != "OK") { 
				$return = false; 
				debug_event('xbmc_del','Error: Unable to del $url from xbmc ' . $ret,'1');
			}
			 

		} // foreach of songs

		return $return;

	} // delete_songs
	

	/**
	 * play
	 * This just tells XBMC to start playing, it does not
	 * take any arguments, it plays the NEXT track on the 
	 * playlist or the first one if it is the first time...
	 */
	function play() { 

		if ($this->XBMCCmd("PlayNext")!="OK") { return false; } 
		return true;

	} // play

	/**
	 * stop
	 * This just tells XBMC to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		if ($this->XBMCCmd("Stop")!="OK") { return false; }
               return true;

	} // stop


	/**
	 * next
	 * This just tells XBMC to skip to the next song 
	 */
	function next() { 

		if ($this->XBMCCmd("PlayNext")!="OK") { return false; }
               return true;

	} // next

	/**
	 * prev
	 * This just tells XBMC to skip to the prev song
	 */
	function prev() { 

		if ($this->XBMCCmd("PlayPrev")!="OK") { return false; }
               return true;
	
	} // prev

	/**
	 * pause
	 * This tells XBMC to pause the current song 
	 */
	function pause() { 
		
		if ($this->XBMCCmd("Pause")!="OK") { return false; }
               return true;

	} // pause 


        /**
        * volume
        * This tells XBMC to set the volume to the parameter
        */
       function volume_set($volume) {

               if ($this->XBMCCmd("SetVolume",$volume)!="OK") { return false; }
               return true;

       } // volume
	   
	   /**
        * xbmc_shutdown
        * This tells XBMC to turn off
        */
       function xbmc_shutdown() {

               if ($this->XBMCCmd("shutdown")!="OK") { return false; }
               return true;

       } // xbmc_shutdown
	   
	   /**
        * clear_playlist
        * This tells XBMC to clear the playlist
        */
       function clear_playlist() {

               if ($this->XBMCCmd("clearplaylist","0")!="OK") { return false; }
               return true;

       } // clear_playlist

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that XBMC currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	function get_songs() { 

		/* Get the Current Playlist */
		//echo $this->XBMCCmd("getcurrentplaylist");
		$playlist = $this->aXBMCCmd("getplaylistcontents","0");
		
		foreach ($playlist as $entry) { 
			$data = array();

			/* Required Elements */
			$data['id'] = get_song_id_from_file(trim(substr($entry,strrpos($entry,"/")+1)));
			$data['raw'] = '';
			

			/* Optional Elements */
			$song = new Song($data['id']);
			$song->format_song();
			$data['name']	=   $song->f_artist . " - " . $song->f_title;

			$results[] = $data;

		} // foreach playlist items 

		return $results;

	} // get_songs

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	function get_status() { 

		/* Construct the Array */
		$array['state'] 	= false; //$this->_mpd->state;
		$array['volume']	= $this->XBMCCmd("GetVolume");

		return $array;

	} // get_status

	/**
	 * connect
	 * This functions tests the connection to XBMC and returns
	 * a boolean value for the status
	 */
	function connect() { 

		if (is_null($this->XBMCCmd("help"))) { return false; } 

		return true;

	} // connect
	
} //end of AmpacheXBMC

?>
