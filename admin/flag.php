<?php
/*

 Copyright (c) Ampache.org
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
require '../lib/init.php';

if (!Access::check('interface','100')) { 
	access_denied();
	exit();
}

show_header(); 

switch ($_REQUEST['action']) {
	case 'edit_song':
		$catalog = new Catalog();
		$song = new Song($_REQUEST['song_id']);
		$new_song = new Song();
	
		/* Setup the vars so we can use the update_song function */ 
		$new_song->title 	= revert_string(scrub_in($_REQUEST['title']));
		$new_song->track 	= revert_string(scrub_in($_REQUEST['track']));
		$new_song->year  	= revert_string(scrub_in($_REQUEST['year']));
		$new_song->comment	= revert_string(scrub_in($_REQUEST['comment']));

		/* If no change in string take Drop down */
		if (strcasecmp(stripslashes($_REQUEST['genre_string']),$song->get_genre_name()) == 0) { 
			$genre = $song->get_genre_name($_REQUEST['genre']);
		}
		else { 
			$genre = scrub_in($_REQUEST['genre_string']);
		}
		
		if (strcasecmp(stripslashes($_REQUEST['album_string']),$song->get_album_name()) == 0) { 
			$album = $song->get_album_name($_REQUEST['album']);
		}
		else { 
			$album = scrub_in($_REQUEST['album_string']);
		}
		
		if (strcasecmp(stripslashes($_REQUEST['artist_string']),$song->get_artist_name()) == 0) { 
			$artist = $song->get_artist_name($_REQUEST['artist']);
		}
		else { 
			$artist = scrub_in($_REQUEST['artist_string']);
		}
	
		/* Use the check functions to get / create ids for this info */
		$new_song->genre = $catalog->check_genre(revert_string($genre));
		$new_song->album = $catalog->check_album(revert_string($album));
		$new_song->artist = $catalog->check_artist(revert_string($artist));
		
		/* Update this mofo, store an old copy for cleaning */
		$old_song 		= new Song();
		$old_song->artist 	= $song->artist;
		$old_song->album	= $song->album;
		$old_song->genre	= $song->genre;
		$song->update_song($song->id,$new_song);

		/* Now that it's been updated clean old junk entries */
		$catalog = new Catalog(); 
		$cleaned = $catalog->clean_single_song($old_song);
		
		/* Add a tagging record of this so we can fix the file */
		if ($_REQUEST['flag']) { 
			$flag = new Flag();
			$flag->add($song->id,'song','retag','Edited Song, auto-tag');
		}

		if (isset($cleaned['artist']) || isset($cleaned['album'])) { $_SESSION['source'] = conf('web_path') . '/index.php'; } 
		
		show_confirmation(_('Song Updated'),_('The requested song has been updated'),$_SESSION['source']);
	break;
	// Show the page for editing a full album
	case 'show_edit_album':

		$album = new Album($_REQUEST['album_id']); 
	
		require_once(conf('prefix') . '/templates/show_edit_album.inc.php'); 

	break;
	// Update all songs from this album
	case 'edit_album':
	
		// Build the needed album 
		$album = new Album($_REQUEST['album_id']); 

		// Create the needed catalog object cause we can't do 
		// static class methods :( 
		$catalog = new Catalog(); 
		$flag = new Flag(); 

		/* Check the new Name */
		$album_id = $catalog->check_album($_REQUEST['name'],$_REQUEST['year']); 

		$songs = $album->get_songs(); 

		foreach ($songs as $song) { 
			// Make that copy and change the album 
			$new_song  = $song;
			$new_song->album = $album_id;

			$song->update_song($song->id,$new_song);

			if ($_REQUEST['flag'] == '1') { 
				$flag->add($song->id,'song','retag','Edited Song, auto-tag');
			} 

		} // end foreach songs

		// Clean out the old album 
		$catalog->clean_albums();

		show_confirmation(_('Album Updated'),'',conf('web_path') . '/admin/index.php'); 

	break;
	// Show the page for editing a full artist
	case 'show_edit_artist':

		$artist = new Artist($_REQUEST['artist_id']); 

		require_once(conf('prefix') . '/templates/show_edit_artist.inc.php'); 		

	break;
	// Update all songs by this artist
	case 'edit_artist':

		// Build the needed artist
		$artist = new Artist($_REQUEST['artist_id']); 

		// Create the needed objects, a pox on PHP4
		$catalog = new Catalog(); 
		$flag = new Flag(); 

		/* Check the new Name */
		$artist_id = $catalog->check_artist($_REQUEST['name']); 

		$songs = $artist->get_songs(); 

		foreach ($songs as $song) { 
			// Make that copy and change the artist
			$new_song = $song; 
			$new_song->artist = $artist_id; 
			
			$song->update_song($song->id,$new_song); 

			if ($_REQUEST['flag'] == '1') { 
				$flag->add($song->id,'song','retag','Edited Song, auto-tag'); 
			} 

		} // end foreach songs

		// Clean out the old artist(s)
		$catalog->clean_artists(); 

		show_confirmation(_('Artist Updated'),'',conf('web_path') . '/admin/index.php'); 

	break; 
	/* Done by 'Select' code passes array of song ids */
	case 'mass_update': 
		$songs = $_REQUEST['song'];	
	        $catalog = new Catalog();
		$object = $_REQUEST['update_field']; 
		$flag = new Flag(); 

		/* If this is an album we need to pull the songs */
		if ($_REQUEST['type'] == 'album') { 

			// Define the results array 
			$results = array(); 

			foreach ($songs as $album_id) { 
				$album = new Album($album_id); 
				$results = array_merge($results,$album->get_song_ids()); 
			} // end foreach albums

			// Re-assign the variable... HACK ALERT :( 
			$songs = $results; 

		} // is album  

		/* Foreach the songs we need to update */
		foreach ($songs as $song_id) { 

			$new_song = new Song($song_id);
	                $old_song               = new Song();
	                $old_song->artist       = $new_song->artist;
	                $old_song->album        = $new_song->album;
	                $old_song->genre        = $new_song->genre;

			/* Restrict which fields can be updated */
			switch ($object) { 
				case 'genre': 
					$new_song->genre = $catalog->check_genre(revert_string($_REQUEST['update_value'])); 
				break;
				case 'album':
					$new_song->album = $catalog->check_album(revert_string($_REQUEST['update_value'])); 
				break;
				case 'artist':
					$new_song->artist = $catalog->check_artist(revert_string($_REQUEST['update_value'])); 
				break; 
				case 'year':
					$new_song->year	= intval($_REQUEST['update_value']); 
				break;
				default: 
					// Rien a faire 
				break;
			} // end switch

	                /* Update this mofo, store an old copy for cleaning */
	                $new_song->update_song($song_id,$new_song);

	                /* Now that it's been updated clean old junk entries */
	                $cleaned = $catalog->clean_single_song($old_song);

			$flag->add($song_id,'song','retag','Edited Song, auto-tag');

		} // end foreach songs

		// Show a confirmation that this worked
		show_confirmation(_('Songs Updated'),'',return_referer()); 
	break; 
	case 'reject_flag':
		$flag_id = scrub_in($_REQUEST['flag_id']);
		$flag = new Flag($flag_id);
		$flag->delete_flag(); 
		$flag->format_name();
		$url = return_referer(); 
		$title = _('Flag Removed');
		$body = _('Flag Removed from') . " " . $flag->name;
		show_confirmation($title,$body,$url);
	break;
	case 'reject_flags':
		$flags = $_REQUEST['song'];

		foreach ($flags as $flag_id) { 
			$flag = new Flag($flag_id); 
			if ($_REQUEST['update_action'] == 'reject') { 
				$flag->delete_flag(); 
			} 
			else { 
				$flag->approve(); 
			} 
		} // end foreach flags
		$title = _('Flags Updated'); 
		show_confirmation($title,'',return_referer()); 
	break;
	case 'show_edit_song':
		$_SESSION['source'] = return_referer();
		$song = new Song($_REQUEST['song']);
		$song->fill_ext_info(); 
		$song->format_song();
		require_once (conf('prefix') . '/templates/show_edit_song.inc.php');
        break;
	case 'disable':
		$song_obj = new Song();
		// If we pass just one, make it still work
	    	if (!is_array($_REQUEST['song_ids'])) { $song_obj->update_enabled(0,$_REQUEST['song_ids']); }
		else {
		    	foreach ($_REQUEST['song_ids'] as $song_id) {
				$song_obj->update_enabled(0,$song_id);
			} // end foreach
		} // end else
		show_confirmation(_('Songs Disabled'),_('The requested song(s) have been disabled'),return_referer());
	break;
	case 'enabled':
		$song_obj = new Song();
		// If we pass just one, make it still work
	        if (!is_array($_REQUEST['song_ids'])) { $song_obj->update_enabled(1,$_REQUEST['song_ids']); }
		else {
		        foreach ($_REQUEST['song_ids'] as $song_id) {
				$song_obj->update_enabled(1,$song_id);
			} // end foreach
		} // end else
	        show_confirmation(_('Songs Enabled'),_('The requested song(s) have been enabled'),return_referer());
        break;
	case 'show_disabled': 
		$disabled = Flag::get_disabled(); 
		Browse::set_type('song'); 
		Browse::set_static_content(1); 
		Browse::save_objects($disabled); 
		Browse::show_objects($disabled); 
	break; 
	default:
	case 'show_flagged':
		$flagged = Flag::get_all();
		Flag::build_cache($flagged); 
		Browse::set_type('flagged'); 
		Browse::set_static_content(1);
		Browse::save_objects($flagged); 
		Browse::show_objects($flagged);
	break;
} // end switch

show_footer();
?>

