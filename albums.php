<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved.

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

require_once('lib/init.php');

show_template('header');

// We'll set any input parameters here
if(!isset($_REQUEST['match'])) { $_REQUEST['match'] = "Browse"; }
if(isset($_REQUEST['match'])) $match = scrub_in($_REQUEST['match']);
if(isset($_REQUEST['album'])) $album = scrub_in($_REQUEST['album']);
if(isset($_REQUEST['artist'])) $artist = scrub_in($_REQUEST['artist']);
$_REQUEST['artist_id'] = scrub_in($_REQUEST['artist_id']);

if ($_REQUEST['action'] === 'clear_art') { 
	if (!$user->has_access('25')) { access_denied(); } 
	$album = new Album($_REQUEST['album_id']);
	$album->clear_art();
	show_confirmation(_('Album Art Cleared'),_('Album Art information has been removed from the database'),"/albums.php?action=show&amp;album=" . $album->id);

} // clear_art
// if we have album
elseif (isset($album)) { 
	$album = new Album($_REQUEST['album']);
	$album->format_album();

	require (conf('prefix') . "/templates/show_album.inc");
	
	/* Get the song ids for this album */
	$song_ids = $album->get_song_ids($_REQUEST['artist']);
	
	show_songs($song_ids,0,$album);
	
} // isset(album)

// Finds the Album art from amazon
elseif ($_REQUEST['action'] === 'find_art') {

	if (!$GLOBALS['user']->has_access('25')) { access_denied(); }
	
	// csammis:  In response to https://ampache.bountysource.com/Task.View?task_id=86,
	// adding retry to album art searching. I hope my PHP style doesn't make vollmer cry,
	// because that would make me cry...then my girlfriend would cry...then my cat would laugh.
	// She's such a little trouper!
	// *NOTE* I knocked it up a notch with some more horrible code :S - Vollmer

	if (!conf('amazon_developer_key')) { 
		echo "<br /><div class=\"fatalerror\">" . _("Error") . ": " . _("No Amazon Developer Key set, amazon album art searching will not work")  . "</div>";
	}

	// get the Album information
        $album = new Album($_REQUEST['album_id']);
	
	if (isset($_REQUEST['artist_name'])) { 
		$artist = scrub_in($_REQUEST['artist_name']);
	} 
	elseif ($album->artist_count == '1') { 
		$artist = $album->artist;
	}

	if (isset($_REQUEST['album_name'])) { 
		$album_name = scrub_in($_REQUEST['album_name']);
	}
	else { 
		$album_name = $album->name;
	}
	
	$search = $artist . " " . $album_name;

	// Attempt to find the art with what we've got
	$images = $album->find_art($_REQUEST['cover'], $search);
	$_SESSION['form']['images'] = $images;

	if (count($images)) {
		include(conf('prefix') . '/templates/show_album_art.inc.php');
	}
	else {
		show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to Amazon being busy, or the album not being present in their collection.'),"/albums.php?action=show&amp;album=" . $album->id);
  	}
  
	$albumname = $album->name;
	$artistname = $artist;
	
	// Remember the last typed entry, if there was one
	if (isset($_REQUEST['album_name'])) {   $albumname = scrub_in($_REQUEST['album_name']); }
	if (isset($_REQUEST['artist_name'])) {  $artistname = scrub_in($_REQUEST['artist_name']); }
	
	include(conf('prefix') . '/templates/show_get_albumart.inc.php');

} // find_art 

// Selecting image from find_art
elseif ($_REQUEST['action'] === 'select_art') { 

	/* Check to see if we have the image url still */
	$image_id = $_REQUEST['image'];
	$album_id = $_REQUEST['album_id'];
	
	$url 	= $_SESSION['form']['images'][$image_id]['url'];
	$mime	= $_SESSION['form']['images'][$image_id]['mime'];
	$snoopy = new Snoopy();
	$snoopy->fetch($url);
	
	$image_data = $snoopy->results;
	
	$album = new Album($album_id);
	$album->insert_art($image_data,$mime);

	show_confirmation(_("Album Art Inserted"),"","/albums.php?action=show&album=$album_id");


} // end select art

// Updates Album from tags
elseif ($_REQUEST['action'] === 'update_from_tags') {

	$album = new Album($_REQUEST['album_id']);

	echo "<br /><b>" . _("Starting Update from Tags") . ". . .</b><br />\n";

	$catalog = new Catalog();
	$catalog->update_single_item('album',$_REQUEST['album_id']);

	echo "<br /><b>" . _("Update From Tags Complete") . "</b> &nbsp;&nbsp;";
	echo "<a href=\"" . conf('web_path') . "/albums.php?action=show&amp;album=" . $_REQUEST['album_id'] . "\">[" . _("Return") . "]</a>";

} // update_from_tags

else {

	if (strlen($_REQUEST['match']) < '1') { $match = 'none'; }

	// Setup the View Ojbect
        $view = new View();
        $view->import_session_view();

	switch($match) {
		case 'Show_all':
			show_alphabet_list('albums','albums.php','show_all');
			show_alphabet_form('',_("Show Albums starting with"),"albums.php?action=match");
			$offset_limit = 99999;
			$sql = "SELECT id FROM album";
			break;
                case 'Show_missing_art':
                        show_alphabet_list('albums','albums.php','show_missing_art');
			show_alphabet_form('',_("Show Albums starting with"),"albums.php?action=match");
                        $offset_limit = 99999;
                        $sql = "SELECT id FROM album where art is null";
                        break; 
		case 'Browse':
		case 'show_albums':
			show_alphabet_list('albums','albums.php','browse');
			show_alphabet_form('',_("Show Albums starting with"),"albums.php?action=match");
			$sql = "SELECT id FROM album";
			break;
		case 'none':
			show_alphabet_list('albums','albums.php','a');
			show_alphabet_form('',_("Show Albums starting with"),"albums.php?action=match");
			$sql = "SELECT id FROM album WHERE name LIKE 'a%'";
			break;
		default:
			//FIXME: This is the old way of doing it, move this to browse 
			show_alphabet_list('albums','albums.php',$match);
			show_alphabet_form($match,_("Show Albums starting with"),"albums.php?action=match");
			echo "<br /><br />";
			$sql = "SELECT id FROM album WHERE name LIKE '$match%'";
	} // end switch

	switch ($_REQUEST['type']) { 
		case 'album_sort':
			if ($match != 'Browse' && $match != 'Show_missing_art' && $match != 'Show_all') { 
				$match_string = " AND album.name LIKE '$match%'";
			}
			unset($_REQUEST['keep_view']);
			$sql = "SELECT album.id, IF(COUNT(DISTINCT(song.artist)) > 1,'Various', artist.name) AS artist_name " . 
				"FROM song,artist,album WHERE song.album=album.id AND song.artist=artist.id $match_string" . 
				"GROUP BY album.name,album.year"; 
			$sort_order = 'artist.name';
		break;
		default:

		break;
	} // switch on special sort types

	// if we are returning
	if ($_REQUEST['keep_view']) { 
                $view->initialize();
	}

	// If we aren't keeping the view then initlize it
	elseif ($sql) {
		if (!$sort_order) { $sort_order = 'name'; } 
		$db_results = mysql_query($sql, dbh());
		$total_items = mysql_num_rows($db_results);
		if ($match != "Show_all") { $offset_limit = $_SESSION['userdata']['offset_limit']; }
		$view = new View($sql, 'albums.php',$sort_order,$total_items,$offset_limit);	
	} 

	else { $view = false; }

	if ($view->base_sql) { 
		$albums = get_albums($view->sql);
		show_albums($albums,$view);	
	}

} // else no album

show_footer();
?>
