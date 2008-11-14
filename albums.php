<?php
/*

 Copyright (c) Ampache.org
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

require_once Config::get('prefix') . '/templates/header.inc.php';

/* Switch on Action */
switch ($_REQUEST['action']) { 
	case 'clear_art':
		if (!$GLOBALS['user']->has_access('75')) { access_denied(); } 
		$album = new Album($_REQUEST['album_id']);
		$album->clear_art();
		show_confirmation(_('Album Art Cleared'),_('Album Art information has been removed from the database'),"/albums.php?action=show&amp;album=" . $album->id);
	break;
	// Upload album art
	case 'upload_art':

		// we didn't find anything 
		if (empty($_FILES['file']['tmp_name'])) { 
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'),"/albums.php?action=show&amp;album=" . $album->id);
			break;
		}

		$album = new Album($_REQUEST['album_id']); 
		
		// Pull the image information
		$data = array('file'=>$_FILES['file']['tmp_name']); 
		$image_data = Album::get_image_from_source($data); 

		// If we got something back insert it
		if ($image_data) { 
			$album->insert_art($image_data,$_FILES['file']['type']);
			show_confirmation(_('Album Art Inserted'),'',"/albums.php?action=show&amp;album=" . $album->id);
		} 
		// Else it failed
		else { 
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'),"/albums.php?action=show&amp;album=" . $album->id);
		} 

	break; 
	case 'find_art':

		// If not a user then kick em out
		if (!Access::check('interface','25')) { access_denied(); exit; }

		// get the Album information
	        $album = new Album($_REQUEST['album_id']);
		$album->format(); 
		$images = array(); 
		$cover_url = array(); 

		// If we've got an upload ignore the rest and just insert it
		if (!empty($_FILES['file']['tmp_name'])) { 
			$path_info = pathinfo($_FILES['file']['name']); 
			$upload['file'] = $_FILES['file']['tmp_name'];
			$upload['mime'] = 'image/' . $path_info['extension']; 
			$image_data = Album::get_image_from_source($upload); 

			if ($image_data) { 
				$album->insert_art($image_data,$upload['0']['mime']); 
				show_confirmation(_('Album Art Inserted'),'',"/albums.php?action=show&amp;album=" . $_REQUEST['album_id']);
				break;

			} // if image data

		} // if it's an upload
		
		// Build the options for our search
		if (isset($_REQUEST['artist_name'])) { 
			$artist = scrub_in($_REQUEST['artist_name']);
		} 
		elseif ($album->artist_count == '1') { 
			$artist = $album->f_artist_name;
		}
		if (isset($_REQUEST['album_name'])) { 
			$album_name = scrub_in($_REQUEST['album_name']);
		}
		else { 
			$album_name = $album->full_name;
		}
	
		$options['artist'] 	= $artist; 
		$options['album_name']	= $album_name; 
		$options['keyword']	= $artist . " " . $album_name; 
		
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
				if ($image['raw']) { 
					unset($images[$index]['raw']); 
				} 
			} // end foreach
			// Store the results for further use
			$_SESSION['form']['images'] = $images;
			require_once Config::get('prefix') . '/templates/show_album_art.inc.php';
		}
		// Else nothing
		else {
			show_confirmation(_('Album Art Not Located'),_('Album Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'),"/albums.php?action=show&amp;album=" . $album->id);
		}
	  
		$albumname = $album->name;
		$artistname = $artist;
		
		// Remember the last typed entry, if there was one
		if (!empty($_REQUEST['album_name'])) {   $albumname = scrub_in($_REQUEST['album_name']); }
		if (!empty($_REQUEST['artist_name'])) {  $artistname = scrub_in($_REQUEST['artist_name']); }
	
		require_once Config::get('prefix') . '/templates/show_get_albumart.inc.php';
	
	break;
	case 'select_art':	

		/* Check to see if we have the image url still */
		$image_id = $_REQUEST['image'];
		$album_id = $_REQUEST['album_id'];
		
		$image 	= Album::get_image_from_source($_SESSION['form']['images'][$image_id]);
		$mime	= $_SESSION['form']['images'][$image_id]['mime'];
	
		$album = new Album($album_id);
		$album->insert_art($image,$mime);

		header("Location:" . Config::get('web_path') . "/albums.php?action=show&album=" . $album->id); 
	break;
	case 'update_from_tags':
		// Make sure they are a 'power' user at least
		if (!Access::check('interface','75')) { 
			access_denied(); 
			exit; 
		} 

		$type 		= 'album'; 
		$object_id 	= intval($_REQUEST['album_id']); 
		$target_url	= Config::get('web_path') . '/albums.php?action=show&amp;album=' . $object_id; 
		require_once Config::get('prefix') . '/templates/show_update_items.inc.php'; 
	break;
	// Browse by Album
	default:
	case 'show':
		$album = new Album($_REQUEST['album']);
		$album->format();
		Browse::reset_filters();
		require Config::get('prefix') . '/templates/show_album.inc.php';
		
	break;
} // switch on view

show_footer();
?>
