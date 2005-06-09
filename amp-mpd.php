<?php  
/*
 *  nj-jukebox.php - Netjuke MPD-based jukebox.
 *  Copyright (C) 2003  Benjamin Carlisle (bcarlisle@24oz.com)
 *  http://mpd.24oz.com/
 *
 *  This has been modified to work with Ampache (http://www.ampache.org) It was 
 *  initially written for NetJuke (http://netjuke.sourceforge.net/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */ 

require_once("modules/init.php");

// Connect to the MPD
if (!class_exists('mpd')) { require_once(conf('prefix') . "/modules/mpd/mpd.class.php"); }
if (!is_object($myMpd)) { $myMpd = new mpd(conf('mpd_host'),conf('mpd_port')); }

if (!$myMpd->connected) {
	echo "<font class=\"error\">" . _("Error Connecting") . ": " . $myMpd->errStr . "</font><br />\n";
	log_event($_SESSION['userdata']['username'],' connection_failed ',"Error: Unable able to connect to MPD, " . $myMpd->errStr);
} 
else {
	switch ($_REQUEST['action']) {
		case "add":
			if (!$user->has_access(25)) { break; }
			$song_ids = array();
			$song_ids[0] = $_REQUEST[song_id];
			addToPlaylist( $myMpd, $song_ids );
			break;
		case "rem":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->PLRemove($_REQUEST[id])) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case ' > ':
		case "play":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->Play()) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "stop":
		case ' X ':
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->Stop()) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case ' = ':
		case "pause":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->Pause()) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case '|< ':
		case "Prev":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->Previous()) ) echo "ERROR: " . $myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case ' >|';
		case "Next":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->Next()) ) echo "ERROR: " . $myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "shuffle":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->PLShuffle()) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "clear":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->PLClear()) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "loop":
			if (!$user->has_access(25)) { break; }
			if ($_REQUEST['val'] == "On") { $_REQUEST['val'] = '1'; }
			else { $_REQUEST['val'] = '0'; }
			if ( is_null($myMpd->SetRepeat($_REQUEST['val'])) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "random":
			if (!$user->has_access(25)) { break; }
                        if ($_REQUEST['val'] == "On") { $_REQUEST['val'] = '1'; }
                        else { $_REQUEST['val'] = '0'; }
			if ( is_null($myMpd->SetRandom($_REQUEST['val']))) echo "ERROR: " .$myMpd->errStr."\n";
                        header ("Location: " . conf('web_path'));
                        break;
		case "adjvol":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->AdjustVolume($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "setvol":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->SetVolume($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "skipto":
			if (!$user->has_access(25)) { break; }
			if ( is_null($myMpd->SkipTo($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
			header ("Location: " . conf('web_path'));
			break;
		case "pladd":
			if (!$user->has_access(25)) { break; }
			$plist = new Playlist( $_REQUEST[pl_id] );
			$song_ids = $plist->get_songs();
			addToPlaylist( $myMpd, $song_ids );
			break;
		case "albadd":
			if (!$user->has_access(25)) { break; }
			$album = new Album( $_REQUEST[alb_id] );
			$song_ids = $album->get_song_ids( );
			addToPlaylist( $myMpd, $song_ids );
			break;
		case "show_control":
			require (conf('prefix') . "/templates/show_mpdplay.inc");
			break;
		default:
			header ("Location: " . conf('web_path'));
			break;
	} // end switch

	// We're done let's disconnect
	$myMpd->Disconnect();
} // end else
?>
