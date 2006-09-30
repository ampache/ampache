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
 * AmpacheIcecast Class
 * the Ampache IceCast Controller, this is quick mod of
 * the MPD class, most functions must be in class itself
 * later, like PidOfFile() etc.
 * Think, it's good idea to integrate public SQL
 * playlists with LocalPlay someway
 * wbr, nikk (nikk[at]nln.ru)
 */
class AmpacheIcecast {

	/* Variables */
	

	/* Constructed variables */
	var $_icecast;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	function AmpacheIcecast() { 
	
		/* Do a Require Once On the needed Libraries */
		//require_once(conf('prefix') . '/modules/icecast/icecast.class.php');

	} // AmpacheIcecast


	/**
	 * function_map
	 * This function returns a named array of the functions
	 * that this player supports and their names in this local
	 * class. This is a REQUIRED function
	 */
	function function_map() { 

                $map = array();

		/* Required Functions */
                $map['add']             = 'add_songs';
                $map['delete']          = 'delete_songs';
                $map['play']            = 'play';
                $map['stop']            = 'stop';
                $map['get']             = 'get_songs';
		$map['status']		= 'get_status';
                $map['connect']         = 'connect';
		
		/* Recommended Functions */
		$map['next']		= 'next';

		/* Optional Functions */
		//$map['delete_all']	= 'clear_playlist';

                return $map;

	} // function_map

	/**
	 * preference
	 * This function returns an array of the preferences and their 
	 * information for Ampache to use All preferences will get a 
	 * localplay_mpd_ appended to their name to avoid conflicts
	 * however this controller does not need to take that into acount
	 * REQUIRE for Locaplay
	 */
	function preferences() { 

		$preferences = array(); 

		$preferences[] = array('name'=>'tracklist','default'=>'/var/tmp/tracklist.txt','type'=>'string','description'=>'Icecast Tracklist');
		$preferences[] = array('name'=>'basedir','default'=>'/var/tmp','type'=>'string','description'=>'Icecast BaseDir');
		$preferences[] = array('name'=>'config','default'=>'/usr/local/etc/ices.conf','type'=>'string','description'=>'Icecast Config');
		$preferences[] = array('name'=>'command','default'=>'/usr/local/bin/ices','type'=>'string','description'=>'Icecast Command');

		return $preferences;

	} // preferences


	/**
	 * add_songs
	 * This must take an array of URL's from Ampache
	 * and then add them to MPD
	 */
	function add_songs($songs) {

		$filename = conf('localplay_icecast_tracklist');
		//echo "$filename " . _("Opened for writing") . "<br>\n";

		/* Open the file for writing */
                if (!$handle = @fopen($filename, "w")) {
                        debug_event('icecast',"Fopen: $filename Failed",'3');
                        return false;
                }

		foreach ($songs as $song_id) { 
			$song = new Song($song_id);
			$url = $song->get_url();
			
		        //echo "$song->file<br>\n";
                        $line = "$song->file\n";
                        if (!fwrite($handle, $line)) {
                                debug_event('icecast',"Fwrite: Unabled to write $line into $filename",'3');
                                return false;
                        } // if write fails


		} // end foreach

                //echo $filename . " " . _("Closed after write") . "<br>\n";
                fclose($handle);

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
		foreach ($songs as $uid) { 

			//if (is_null($this->_icecast->plremove($uid))) { $return = false; }

		} // foreach of songs

		//return $return;
		return $false;

	} // delete_songs
	

	/**
	 * play
	 * This just tells ICES to start playing, it does not
	 * take any arguments
	 */
	function play() {

		// get the pid from basedir and reload tracklist information
		$pid = conf('localplay_icecast_basedir') . "/ices.pid";

   		$PrevPid = file_get_contents($pid);

   		if(($PrevPid !== FALSE) && posix_kill(rtrim($PrevPid),0)) {
       			//echo "Error: Server is already running with PID: $PrevPid\n";

			// Causes ices to exit (can be in stop function)
			//$restart = "kill -s SIGTERM " . $PrevPid;
			// Causes ices to close and reopen the log file and  the  playlist
			$reload = "kill -s SIGHUP " . $PrevPid;
			// Causes  ices  to  skip  to  the  next  track  in  the   playlist immediately. 
			$next = "kill -s SIGUSR1 " . $PrevPid;
			exec($reload);
			exec($next);
       			//exit(-99);
   		} else {
       			//echo "Starting Server...\n";
			
			$cmd = conf('localplay_icecast_command') . " -c " . conf('localplay_icecast_config') . " -F " . conf('localplay_icecast_tracklist') . " -B";
                        debug_event('icecast',"Exec: $cmd",'5');
                	exec($cmd);

   		}

		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 
		
		// get the pid from basedir and reload tracklist information
                $pid = conf('localplay_icecast_basedir') . "/ices.pid";

                $PrevPid = file_get_contents($pid);

                if(($PrevPid !== FALSE) && posix_kill(rtrim($PrevPid),0)) {
			// Causes ices to exit (can be in stop function)
                        $stop = "kill -s SIGTERM " . $PrevPid;
			exec($stop);
			return true;
		}

		return false;

	} // stop


	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	function next() { 

                // get the pid from basedir and reload tracklist information
                $pid = conf('localplay_icecast_basedir') . "/ices.pid";

                $PrevPid = file_get_contents($pid);

                if(($PrevPid !== FALSE) && posix_kill(rtrim($PrevPid),0)) {
                        // Causes ices to exit (can be in stop function)
                        $next = "kill -s SIGUSR1 " . $PrevPid;
			exec($next);
                        return true;
                }

                return false;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that ICES currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	function get_songs() { 

		/* Get the Current Playlist */
		
		$source = conf('localplay_icecast_tracklist');
		$playlist = array_map('rtrim',file($source));

		$i = 1;

		foreach ($playlist as $entry) {

			$data = array();

			/* Required Elements */
			$data['id'] 	= $i;
			//$data['raw']	= $entry;

			/* Parse out the song ID and then create the song object */
			$sql = "SELECT id FROM song WHERE file LIKE '" . sql_escape($entry) . "'";
			$db_results = mysql_query($sql, dbh());
			$dbdata = mysql_fetch_assoc($db_results);

			$song = new Song($dbdata['id']);
			$song->format_song();
			$data['name']	= $song->f_artist . ' - ' . $song->f_title;
			//$data['name']	= $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;

			/* Just incase prevent emtpy names */
			if (!$song->title) { $data['name'] = _('Unknown'); }

			/* Optional Elements */
			$data['link']   = '';
			$data['track']	= $i;

			$results[] = $data;
			$i++;

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
		//$array['state'] 	= $this->_icecast->state;
		//$array['repeat']	= $this->_icecast->repeat;
		//$array['random']	= $this->_icecast->random;

		//return $array;

	} // get_status

	/**
	 * connect
	 * This functions creates the connection to MPD and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	function connect() {

                // ICECAST server checkout
		// can be implemented here
		// is it running?
		// so we can put ICECAST host, port, url
		// and check against it...

		return true;

	} // connect
	
} //end of AmpacheIcecast

?>
