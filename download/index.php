<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*!
	@header Download Document
	@discussion Downloads a song to the user, if they have download permission.
	Special thanks to the Horde project for their Browser class that makes this so easy.
*/

require('../lib/init.php');
require(conf('prefix') . '/modules/horde/Browser.php');

$browser = new Browser();

/* If we are running a demo, quick while you still can! */
if (conf('demo_mode') || !$GLOBALS['user']->has_access('25') || !$GLOBALS['user']->prefs['download']) {
	debug_event('access_denied',"Download Access Denied, " . $GLOBALS['user']->username . " doesn't have sufficent rights",'3');
	access_denied();
}

/*
   If they are using access lists let's make sure
   that they have enough access to play this mojo
*/
if (conf('access_control')) {
        $access = new Access(0);
        if (!$access->check('stream', $_SERVER['REMOTE_ADDR'],$GLOBALS['user']->id,'50') ||
		!$access->check('network', $_SERVER['REMOTE_ADDR'],$GLOBALS['user']->id,'50')) {
                debug_event('access_denied', "Download Access Denied, " . $_SERVER['REMOTE_ADDR'] . " does not have download level",'3');
                access_denied();
        }
} // access_control is enabled

/* Check for a song id */
if (!$_REQUEST['song_id']) { 
	echo "Error: No Song found, download failed";
	debug_event('download','No Song found, download failed','2');
}

/* If we're got require_session check for a valid session */
if (conf('require_session')) { 
	if (!session_exists(scrub_in($_REQUEST['sid']))) { 
		die(_("Session Expired: please log in again at") . " " . conf('web_path') . "/login.php");
		debug_event('session_expired',"Download Access Denied: " . $GLOBALS['user']->username . "'s session has expired",'3');
	}
} // if require_session
	

/* If the request is to download it... why is this here? */
if ($_REQUEST['action'] == 'download') {
	$song = new Song($_REQUEST['song_id']);
	$song->format_song();
	$song->format_type();
	$song_name = str_replace('"'," ",$song->f_artist_full . " - " . $song->title . "." . $song->type);

	/* Because of some issues with IE remove ? and / from the filename */
	$song_name = str_replace(array('?','/','\\'),"_",$song_name);
	
	// Use Horde's Browser class to send the right headers for different browsers
	// Should get the mime-type from the song rather than hard-coding it.
	header("Content-Length: " . $song->size);
	$browser->downloadHeaders($song_name, $song->mime, false, $song->size);
	$fp = fopen($song->file, 'r');
	
	/* We need to check and see if throttling is enabled */
	$speed = intval(conf('throttle_download'));
	if ($speed > 0) { 
		while(!feof($fp)) {
			echo fread($fp, round($speed*1024));
			flush();
			sleep(1);
		}
	} // if limiting 
	/* Otherwise just pump it out as fast as you can */	
	else { 	
		fpassthru($fp);
	} // else no limit

	fclose($fp);

} // If they've requested a download

?>
