<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*

 Do most of the dirty work of displaying the mp3 catalog

*/

require_once("modules/init.php");

if (!isset($_REQUEST['match'])) { $_REQUEST['match'] = "Browse"; }
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = "match"; }
$action = scrub_in($_REQUEST['action']);

show_template('header');


switch($action) {
    case 'show':
    case 'Show':
	show_alphabet_list('artists','artists.php');
	$artist = new Artist(scrub_in($_REQUEST['artist']));
	$artist->show_albums();
	break;

    case 'show_all_songs':
        $artist = get_artist_name(scrub_in($_REQUEST['artist']));
        echo "<h2>" . _("All songs by") . " $artist</h2>";
	$song_ids = get_song_ids_from_artist($_REQUEST['artist']);
        show_songs($song_ids);
        break;

    case 'update_from_tags':

        $artist = new Artist($_REQUEST['artist']);

        echo "<br /><b>" . _("Starting Update from Tags") . ". . .</b><br />\n";

        $catalog = new Catalog();
        $catalog->update_single_item('artist',$_REQUEST['artist']);

        echo "<br /><b>" . _("Update From Tags Complete") . "</b> &nbsp;&nbsp;";
        echo "<a href=\"" . conf('web_path') . "/artists.php?action=show&amp;artist=" . $_REQUEST['artist'] . "\">[" . _("Return") . "]</a>";

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
		
			//the manual rename takes priority	
			if ($_POST['artist_name'] != "") {
				//then just change the name of the artist in the db
				$newid = $artist->rename($_POST['artist_name']);
			
			}
			elseif ($_POST['artist_id'] != $artist->id) {
				if ($_POST['test_stats'] == 'yes') {
					$catalog->merge_stats("artist",$artist->id,$_POST['artist_id']);
				} 
				else {
				//merge with other artist
					$artist->merge($_POST['artist_id']);
					$newid = $_POST['artist_id'];
				}
			} // elseif different artist and id 
			
			//now flag for id3tag update if selected, and song id changed
			if ($_POST['update_id3'] == "yes" && $newid != $artist->id) {
			
				/* Set the rename information in the db */
				foreach ($songs as $song) {
					$flag_qstring = "REPLACE INTO flagged " . 
						"SET type = 'setid3', song = '" . $song->id . "', date = '" . time() . "', user = '" . $GLOBALS['user']->username . "'";
	            			mysql_query($flag_qstring, dbh()); 
	    			}
				
			} // end if they wanted to update
		
		}  // if we've got the needed variables

		/* Else we've got an error! */
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
		preg_match("/^(\w*)/", $match, $matches);
		show_alphabet_list('artists','artists.php',$match);
		if ($match === "Browse") {
			show_alphabet_form('',_("Show Artists starting with"),"artists.php?action=match");
			show_artists();
		}
		elseif ($match === "Show_all") {
			show_alphabet_form('',_("Show Artists starting with"),"artists.php?action=match");
			$_SESSION['view_offset_limit'] = 999999;
			show_artists();
		}		
	        else {
			$chr = preg_replace("/[^a-zA-Z0-9]/", "", $matches[1]);
			show_alphabet_form($chr,_("Show Artists starting with"),"artists.php?action=match");
	
			if ($chr == '') {
				show_artists('A');
			}
			else {
				show_artists($chr);
			}
		}
	break;	
	default:
		//FIXME: This is being moved to browse
		show_alphabet_list('artists','artists.php');
		show_alphabet_form('',_("Show Artists starting with"),"artists.php?action=match");
		show_artists('A');
	break;
} // end switch

show_footer();
?>
