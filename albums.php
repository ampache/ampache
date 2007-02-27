<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All Rights Reserved.

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

show_template('header');

// We'll set any input parameters here
if(!isset($_REQUEST['match'])) { $_REQUEST['match'] = "Browse"; }
if(isset($_REQUEST['match'])) $match = scrub_in($_REQUEST['match']);
if(isset($_REQUEST['album'])) $album = scrub_in($_REQUEST['album']);
if(isset($_REQUEST['artist'])) $artist = scrub_in($_REQUEST['artist']);
$_REQUEST['artist_id'] = scrub_in($_REQUEST['artist_id']);
$min_album_size = conf('min_object_count');
if ($min_album_size == '') { 
	$min_album_size = '0';
}

$action = scrub_in($_REQUEST['action']); 

/* Switch on Action */
switch ($action) { 
	case 'clear_art':
		if (!$GLOBALS['user']->has_access('75')) { access_denied(); } 
		$album = new Album($_REQUEST['album_id']);
		$album->clear_art();
		show_confirmation(_('Album Art Cleared'),_('Album Art information has been removed from the database'),"/albums.php?action=show&amp;album=" . $album->id);
	break;
	case 'show':	
		$album = new Album($_REQUEST['album']);
		$album->format();

		require (conf('prefix') . '/templates/show_album.inc');
	
		/* Get the song ids for this album */
		$song_ids = $album->get_song_ids($_REQUEST['artist']);
	
		show_songs($song_ids,0,$album);
	break;
	// Upload album art
	case 'upload_art':

		// we didn't find anything 
		if (empty($_FILES['file']['tmp_name'])) { 
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received corectly.'),"/albums.php?action=show&amp;album=" . $album->id);
			break;
		}

		$album = new Album($_REQUEST['album_id']); 
		
		// Pull the image information
		$data = array('file'=>$_FILES['file']['tmp_name']); 
		$image_data = get_image_from_source($data); 

		// If we got something back insert it
		if ($image_data) { 
			$album->insert_art($image_data,$_FILES['file']['type']);
			show_confirmation(_('Album Art Inserted'),'',"/albums.php?action=show&album=" . $album->id);
		} 
		// Else it failed
		else { 
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received corectly.'),"/albums.php?action=show&amp;album=" . $album->id);
		} 

	break; 
	case 'find_art':

		// If not a user then kick em out
		if (!$GLOBALS['user']->has_access('25')) { access_denied(); exit; }

		// get the Album information
	        $album = new Album($_REQUEST['album_id']);
		$images = array(); 
		$cover_url = array(); 

		// If we've got an upload ignore the rest and just insert it
		if (!empty($_FILES['file']['tmp_name'])) { 
			$path_info = pathinfo($_FILES['file']['name']); 
			$upload['file'] = $_FILES['file']['tmp_name'];
			$upload['mime'] = 'image/' . $path_info['extension']; 
			$image_data = get_image_from_source($upload); 

			if ($image_data) { 
				$album->insert_art($image_data,$upload['0']['mime']); 
				show_confirmation(_('Album Art Inserted'),'',"/albums.php?action=show&album=" . $_REQUEST['album_id']);
				break;

			} // if image data

		} // if it's an upload
		
		// Build the options for our search
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
	
		$options['artist'] 	= $artist; 
		$options['album_name']	= $album_name; 
		$options['keyword']	= $artist . " " . $album_name; 
		// HACK that makes baby jesus cry...
		$options['skip_id3']	= true; 
	
		// Attempt to find the art. 
		$images = $album->find_art($options,'6');

		if (!empty($_REQUEST['cover'])) { 
			$path_info = pathinfo($_REQUEST['cover']); 
			$cover_url[0]['url'] 	= scrub_in($_REQUEST['cover']); 
			$cover_url[0]['mime'] 	= 'image/' . $path_info['extension'];
		}
		$images = array_merge($cover_url,$images); 

		// If we've found anything then go for it!		
		if (count($images)) {
			// We don't want to store raw's in here so we need to strip them out into a seperate array
			foreach ($images as $index=>$image) { 
				if (isset($image['raw'])) { 
					//unset($images[$index]); 
					$images[$index]['raw'] = ''; 
				} 
			} // end foreach  

			// Store the results for further use
			$_SESSION['form']['images'] = $images;
			require_once(conf('prefix') . '/templates/show_album_art.inc.php');
		}
		// Else nothing
		else {
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received corectly.'),"/albums.php?action=show&amp;album=" . $album->id);
		}
	  
		$albumname = $album->name;
		$artistname = $artist;
		
		// Remember the last typed entry, if there was one
		if (isset($_REQUEST['album_name'])) {   $albumname = scrub_in($_REQUEST['album_name']); }
		if (isset($_REQUEST['artist_name'])) {  $artistname = scrub_in($_REQUEST['artist_name']); }
	
		require_once(conf('prefix') . '/templates/show_get_albumart.inc.php');
	
	break;
	case 'select_art':	

		/* Check to see if we have the image url still */
		$image_id = $_REQUEST['image'];
		$album_id = $_REQUEST['album_id'];
		
		$image 	= get_image_from_source($_SESSION['form']['images'][$image_id]);
		$mime	= $_SESSION['form']['images'][$image_id]['mime'];
	
		$album = new Album($album_id);
		$album->insert_art($image,$mime);


		show_confirmation(_('Album Art Inserted'),'',"/albums.php?action=show&album=$album_id");
	break;
	case 'update_from_tags':
	
		$album = new Album($_REQUEST['album_id']);

		show_box_top(_('Starting Update from Tags')); 

		$catalog = new Catalog();
		$catalog->update_single_item('album',$_REQUEST['album_id']);

		echo "<br /><b>" . _('Update From Tags Complete') . "</b> &nbsp;&nbsp;";
		echo "<a href=\"" . conf('web_path') . "/albums.php?action=show&amp;album=" . scrub_out($_REQUEST['album_id']) . "\">[" . _('Return') . "]</a>";
		show_box_bottom(); 
	break;
	// Browse by Album
	default: 
		if (strlen($_REQUEST['match']) < '1') { $match = 'a'; }

		// Setup the View Ojbect
	        $view = new View();
	        $view->import_session_view();

		if ($match == 'Show_all' || $match == 'Show_missing_art' || $match == 'Browse') { $chr = ''; } 
		else { $chr = $match; } 

		require (conf('prefix') . '/templates/show_box_top.inc.php');
		show_alphabet_list('albums','albums.php',$match);
		show_alphabet_form($chr,_('Show Albums starting with'),"albums.php?action=match");
		require (conf('prefix') . '/templates/show_box_bottom.inc.php');
	
		switch($match) {
			case 'Show_all':
				$offset_limit = 99999;
	                        $sql = "SELECT album.id FROM song,album ".
	                               " WHERE song.album=album.id ".
	                               "GROUP BY song.album ".
	                               "  HAVING COUNT(song.id) > $min_album_size ";
			break;
	                case 'Show_missing_art':
	                        $offset_limit = 99999;
	                        $sql = "SELECT album.id FROM song,album ".
	                               " WHERE song.album=album.id ".
	                               "   AND album.art is null ".
	                               "GROUP BY song.album ".
	                               "  HAVING COUNT(song.id) > $min_album_size ";
                        break; 
			case 'Browse':
			case 'show_albums':
                	        $sql = "SELECT album.id FROM song,album ".
	                               " WHERE song.album=album.id ".
	                               "GROUP BY song.album ".
	                               "  HAVING COUNT(song.id) > $min_album_size ";
			break;
			default:
        	                $sql = "SELECT album.id FROM song,album ".
	                               " WHERE song.album=album.id ".
                	               "   AND album.name LIKE '$match%'".
	                               "GROUP BY song.album ".
	                               "  HAVING COUNT(song.id) > $min_album_size ";
		} // end switch

		switch ($_REQUEST['type']) { 
			case 'album_sort':
				if ($match != 'Browse' && $match != 'Show_missing_art' && $match != 'Show_all') { 
					$match_string = " AND album.name LIKE '$match%'";
				}
				$sort_sql = "SELECT album.id, IF(COUNT(DISTINCT(song.artist)) > 1,'Various', artist.name) AS artist_name " . 
					"FROM song,artist,album WHERE song.album=album.id AND song.artist=artist.id $match_string" . 
					"GROUP BY album.name,album.year ".
	                                "HAVING COUNT(song.id) > $min_album_size ";
				$sort_order = 'artist.name';
			break;
			default:
	
			break;
		} // switch on special sort types

		// if we are returning
		if ($_REQUEST['keep_view']) { 
	                $view->initialize($sort_sql);
		}

		// If we aren't keeping the view then initlize it
		elseif ($sql) {
			if (!$sort_order) { $sort_order = 'name'; } 
			$db_results = mysql_query($sql, dbh());
			$total_items = mysql_num_rows($db_results);
			if ($match != "Show_all") { $offset_limit = $user->prefs['offset_limit']; }
			$view = new View($sql, 'albums.php',$sort_order,$total_items,$offset_limit);	
		} 
	
		else { $view = false; }
	
		if ($view->base_sql) { 
			$albums = get_albums($view->sql);
			require conf('prefix') . '/templates/show_albums.inc.php';
		}
	
	break;
} // end switch on action

show_footer();
?>
