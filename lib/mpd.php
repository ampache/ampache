<?php
/*

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

/*!
	@function addToPlaylist() 
	@discussion adds a bunch of songs to the mpd playlist
		this takes a mpd object, and an array of songs
*/
function addToPlaylist( $myMpd, $song_ids=array()) {

       	foreach( $song_ids as $song_id ) {

		/* There are two ways to do this, filename or URL */
		if (conf('mpd_method') == 'url') { 
			// We just need to generate a standard stream URL and pass that
			$song = new Song($song_id);
			$sess_id = session_id();
			if ($song->type == ".flac") { $song->type = ".ogg"; }
			if ($GLOBALS['user']->prefs['play_type'] == 'downsample') { 
				$ds = $GLOBALS['user']->prefs['sample_rate'];
			}
			$song_url = conf('web_path') . "/play/index.php?song=$song_id&uid=" . $GLOBALS['user']->username . "&sid=$sess_id&ds=$ds&name=." . $song->type;
			if (is_null( $myMpd->PlAdd($song_url) ) ) { 
				$log_line = _("Error") . ": " . _("Could not add") . ": " . $song_url . " : " . $myMpd->errStr;
				echo "<font class=\"error\">$log_line</font><br />\n";
				if (conf('debug')) { log_event($GLOBALS['user']->username,'add',$log_line); }
			} // if it's null
		} // if we want urls
		else {
	                $song = new Song( $song_id );
	                $song_filename = $song->get_rel_path();
	                if( is_null( $myMpd->PLAdd( $song_filename ) ) ) {
				$log_line =  _("Error") . ": " . _("Could not add") . ": " . $song_filename . " : " . $myMpd->errStr;
				echo "<font class=\"error\">$log_line</font><br />\n";
				if (conf('debug')) { log_event($_SESSION['userdata']['username'],'add',$log_line); }
		        } // end if it's null
			// We still need to count if they use the file method	
			else {
	                        $GLOBALS['user']->update_stats( $song_id );
	               	} // end else

		} // end else not url method
       	} // end foreach 

} // addToPlaylist

/*!
	@function show_mpd_control
	@discussion shows the mpd controls
*/
function show_mpd_control() { 

	$_REQUEST['action'] = 'show_control';
	require ('amp-mpd.php');


} // show_mpd_control

function show_mpd_pl() {
        require (conf('prefix').'/templates/show_mpdpl.inc');
} // show_mpd_pl

?>
