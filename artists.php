<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

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

require_once 'lib/init.php';

show_header(); 

/**
 * Display Switch 
 */
switch($_REQUEST['action']) {
	case 'show':
		$artist = new Artist($_REQUEST['artist']);
		$artist->format(); 
		$object_ids = $artist->get_albums(); 
		$object_type = 'album'; 
		require_once Config::get('prefix') . '/templates/show_artist.inc.php';
		break;
	case 'show_all_songs':
	    	$artist = new Artist($_REQUEST['artist']);
		$artist->format();
		$object_type = 'song'; 
		$object_ids = $artist->get_songs(); 
		require_once Config::get('prefix') . '/templates/show_artist.inc.php';
        break;
	case 'update_from_tags':

		$type		= 'artist'; 
		$object_id	= intval($_REQUEST['artist']); 
		$target_url	= Config::get('web_path') . "/artists.php?action=show&amp;artist=" . $object_id; 
		require_once Config::get('prefix') . '/templates/show_update_items.inc.php'; 
	break;
	case 'rename_similar':
		if (!$user->has_access('100')) { access_denied(); }
		$count = 0;
		if (isset($_REQUEST['artist']) && is_numeric($_REQUEST['artist']) && isset($_REQUEST['artists']) && is_array($_REQUEST['artists'])) {
			$artist = new Artist($_REQUEST['artist']);
			if ($artist->id)
			foreach ($_REQUEST['artists'] as $artist_id) {
				if (is_numeric($artist_id)) {
					$that_artist = new Artist($artist_id);
					if ($that_artist->id) {
						$that_artist->merge($artist->id);
						$count++;
					} else
						$GLOBALS['error']->add_error('general',"Error: No such artist '$artist_id'");
				} else {
					$GLOBALS['error']->add_error('general',"Error: '$artist_id' is not a valid ID");
				}
			}
			else
				$GLOBALS['error']->add_error('general',"Error: No such artist '" . $_REQUEST['artist'] . "'");
		} else {
			$GLOBALS['error']->add_error('general',"Error: Errenous request");
		}
		if ($count > 0) {
			show_confirmation (
				"Renamed artist(s)",
				"$count artists have been merged with " . $artist->name,
				conf('web_path') . "/artists.php?action=show&artist=" . $artist->id
			);
		} else {
			$GLOBALS['error']->print_error('general');
		}
		
	break;
	case 'show_similar':
		if (!$GLOBALS['user']->has_access('75')) { 
			access_denied(); 
			exit; 
		}
		
		$artist = new Artist($_REQUEST['artist']);
		//options
		$similar_artists = $artist->get_similar_artists(
						make_bool($_POST['n_rep_uml']),
						$_POST['n_filter'],
						$_POST['n_ignore'],
						$_POST['c_mode'],
						$_POST['c_count_w'],
						$_POST['c_percent_w'],
						$_POST['c_distance_l'],
						make_bool($_POST['c_ignins_l']));
		$artist_id = $artist->id;
		$artist_name = $artist->name;
		require Config::get('prefix') . '/templates/show_similar_artists.inc.php';
		 
	break;
	case 'rename':
		//die if not enough permissions
		if (!$user->has_access('100')) { access_denied(); }
			
		/* Get the artist */
		$artist = new Artist($_REQUEST['artist']);
		$catalog = new Catalog();
		
		//check if we've been given a target
		if ((isset($_POST['artist_id']) && $_POST['artist_id'] != $artist->id ) || (isset($_POST['artist_name']) &&  $_POST['artist_name'] != "")) {
		
			//if we want to update id3 tags, then get the array of ids now, it's too late afterwards
			if (make_bool($_POST['update_id3']))
				$songs = $artist->get_songs(); 
			
			$ret = 0;
			//the manual rename takes priority, but if they tested out the insert thing ignore
			if ($_POST['artist_name'] != "" && $_POST['artist_name'] != $artist->name) {
				//then just change the name of the artist in the db
				$ret = $artist->rename($_POST['artist_name']);
				$newid = $ret;
				$newname = $_POST['artist_name'];
			}
			//new id?
			elseif ($_POST['artist_id'] != $artist->id) {
				//merge with other artist
				$ret = $artist->merge($_POST['artist_id']);
				$newid = $_POST['artist_id'];
				$newname = $ret;
			} // elseif different artist and id 
			//if no changes, no changes
			
			//now flag for id3tag update if selected, and something actually happaned
			if ($ret && make_bool($_POST['update_id3'])) {
			
				/* Set the rename information in the db */
				foreach ($songs as $song) {
					$flag = new Flag();
					$flag->add($song->id,"song","retag","Renamed artist, retag");
					$flag_qstring = "REPLACE INTO flagged " . 
						"SET type = 'setid3', song = '" . $song->id . "', date = '" . time() . "', user = '" . $GLOBALS['user']->username . "'";
	            			mysql_query($flag_qstring, dbh()); 
	    			}
				
			} // end if they wanted to update
			
			// show something other than a blank screen after this			
			if ($ret) {
				show_confirmation (
					"Renamed artist",
					$artist->name . " is now known as " . $newname,
					conf('web_path') . "/artists.php?action=show&artist=" . $newid
				);
			}
		
		}  // if we've got the needed variables

		/* Else we've got an error! But be lenient, and just show the form again */
		else { 
			require (conf('prefix') . '/templates/show_rename_artist.inc.php');
		}
    	break;	
	case 'show_rename':
		$artist = new Artist($_REQUEST['artist']);
		require (conf('prefix') . '/templates/show_rename_artist.inc.php'); 
	break;
	case 'match':
	case 'Match':
		$match = scrub_in($_REQUEST['match']);
		if ($match == "Browse" || $match == "Show_all") { $chr = ""; }
		else { $chr = $match; } 
		/* Enclose this in the purty box! */
		require (conf('prefix') . '/templates/show_box_top.inc.php'); 
		show_alphabet_list('artists','artists.php',$match);
		show_alphabet_form($chr,_('Show Artists starting with'),"artists.php?action=match");
		require (conf('prefix') . '/templates/show_box_bottom.inc.php');

		if ($match === "Browse") {
			show_artists();
		}
		elseif ($match === "Show_all") {
			$offset_limit = 999999;
			show_artists();
		}		
	        else {
			if ($chr == '') {
				show_artists('A');
			}
			else {
				show_artists($chr);
			}
		}
	break;	
} // end switch

show_footer();
?>
