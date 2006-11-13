<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

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
	@header Admin Catalog
	This document handles actions for catalog creation and passes them off to the catalog class
*/

require('../lib/init.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
}


/* Set any vars we are going to need */
$catalog = new Catalog($_REQUEST['catalog_id']);

show_template('header');

/* Big switch statement to handle various actions */
switch ($_REQUEST['action']) {
	case 'fixed':
		delete_flagged($flag);
		$type = 'show_flagged_songs';
		include(conf('prefix') . '/templates/flag.inc');
	break;
	case 'add_to_all_catalogs':
		$catalog = new Catalog();
		$_REQUEST['catalogs'] = $catalog->get_catalog_ids();
	case 'add_to_catalog':
	    	if (conf('demo_mode')) { break; }
		if ($_REQUEST['catalogs'] ) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				echo "<div class=\"confirmation-box\">";
				$catalog = new Catalog($catalog_id);
				$catalog->add_to_catalog('fast_add');
				echo "</div>";
			}
	       	}
		$url 	= conf('web_path') . '/admin/index.php';
		$title 	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'update_all_catalogs':
		$catalog = new Catalog();
		$_REQUEST['catalogs'] = $catalog->get_catalog_ids();
	case 'update_catalog':
	    	/* If they are in demo mode stop here */
	        if (conf('demo_mode')) { break; }

		if (isset($_REQUEST['catalogs'])) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				echo "<div class=\"confirmation-box\">";
				$catalog = new Catalog($catalog_id);
				$catalog->verify_catalog($catalog_id->id,$_REQUEST['update_type']);
				echo "</div>\n";
			}
		}
		$url	= conf('web_path') . '/admin/index.php';
		$title	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'full_service':
		$catalog = new Catalog();
		/* Make sure they aren't in demo mode */
		if (conf('demo_mode')) { break; } 

		if (!$_REQUEST['catalogs']) { 
			$_REQUEST['catalogs'] = $catalog->get_catalog_ids();
		}

		/* This runs the clean/verify/add in that order */
		foreach ($_REQUEST['catalogs'] as $catalog_id) { 
			echo "<div class=\"confirmation-box\">";
			$catalog = new Catalog($catalog_id);
			$catalog->clean_catalog();
			$catalog->count = 0;
			$catalog->verify_catalog();
			$catalog->count = 0;
			$catalog->add_to_catalog();
			echo "</div>";
		} 		
		$url	= conf('web_path') . '/admin/index.php';
		$title	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'delete_catalog':
		/* Make sure they aren't in demo mode */
	        if (conf('demo_mode')) { break; }
	
		/* Delete the sucker, we don't need to check perms as thats done above */
		$catalog = new Catalog($_REQUEST['catalog_id']);
		$catalog->delete_catalog();
		$next_url = conf('web_path') . '/admin/index.php';
		show_confirmation(_('Catalog Deleted'),_('The Catalog and all assoicated records has been deleted'),$nexturl);
	break;
	case 'remove_disabled':
	        if (conf('demo_mode')) { break; }

		$song = $_REQUEST['song'];

		if (count($song)) { 
			$catalog->remove_songs($song);
			$body = _('Songs Removed');
		}
		else {
			$body = _('No Songs Removed');
		}
		$url	= conf('web_path') . '/admin/index.php';
		$title	= _('Disabled Songs Processed');
		show_confirmation($title,$body,$url);
	break;
	case 'clean_all_catalogs':
		$catalog = new Catalog(); 
		$_REQUEST['catalogs'] = $catalog->get_catalog_ids();
	case 'clean_catalog':
	    	/* If they are in demo mode stop them here */
	        if (conf('demo_mode')) { break; }
	
		// Make sure they checked something
		if (isset($_REQUEST['catalogs'])) {	
			foreach($_REQUEST['catalogs'] as $catalog_id) { 
				echo "<div class=\"confirmation-box\">";
				$catalog = new Catalog($catalog_id);
				$catalog->clean_catalog(0,1);
				echo "</div>";
			} // end foreach catalogs
		}
		
		$url 	= conf('web_path') . '/admin/index.php';
		$title	= _('Catalog Cleaned');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'update_catalog_settings':
		/* No Demo Here! */
        	if (conf('demo_mode')) { break; }

		/* Update the catalog */
		$catalog = new Catalog();
		$catalog->update_settings($_REQUEST);
		
		$url 	= conf('web_path') . '/admin/index.php';
		$title 	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	// FIXME!
	case 'add_catalog':
		/* Wah Demo! */
		if (conf('demo_mode')) { break; }
		
		if ($_REQUEST['path'] AND $_REQUEST['name']) { 
			/* Throw all of the album art types into an array */
			$art = array('id3'=>$_REQUEST['art_id3v2'],'amazon'=>$_REQUEST['art_amazon'],'folder'=>$_REQUEST['art_folder']);

			/* Enclose it in a purrty box! */
			echo "<div class=\"confirmation-box\">";
			
			/* Create the Catalog */
			$catalog->new_catalog($_REQUEST['path'],
					$_REQUEST['name'],
					$_REQUEST['key'],
					$_REQUEST['rename_pattern'],
					$_REQUEST['sort_pattern'],
					$_REQUEST['type'],
					$_REQUEST['gather_art'],
					$_REQUEST['parse_m3u'],
					$art);

			echo "</div>\n";

			$url = conf('web_path') . '/admin/index.php';
			$title = _('Catalog Created');
			$body  = _('Catalog Created and Songs Indexed');
			show_confirmation($title,$body,$url);
		}
		else {
			$error = "Please complete the form.";
			include(conf('prefix') . '/templates/show_add_catalog.inc.php');
		}
	break;
	case 'clear_stats':
    		if (conf('demo_mode')) { break; }
		
		clear_catalog_stats();
		$url	= conf('web_path') . '/admin/index.php';
		$title	= _('Catalog statistics cleared');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'show_add_catalog':
		include(conf('prefix') . '/templates/show_add_catalog.inc.php');
	break;
	case 'clear_now_playing':
	        if (conf('demo_mode')) { break; }
	    	clear_now_playing();
		show_confirmation(_('Now Playing Cleared'),_('All now playing data has been cleared'),conf('web_path') . '/admin/index.php');
	break;
	case 'show_clear_stats':
		/* Demo Bad! */
	        if (conf('demo_mode')) { break; }

		$url 	= conf('web_path') . '/admin/catalog.php?action=clear_stats';
		$body	= _('Do you really want to clear the statistics for this catalog?');
		$title	= _('Clear Catalog Stats'); 
		show_confirmation($title,$body,$url,1);
	break;
	case 'show_disabled':
	        if (conf('demo_mode')) { break; }
		
		$songs = $catalog->get_disabled();
		if (count($songs)) { 
			require (conf('prefix') . '/templates/show_disabled_songs.inc');
		}
		else {
			echo "<div class=\"error\" align=\"center\">" . _('No Disabled songs found') . "</div>";
		}
	break;
	case 'show_delete_catalog':
		/* Stop the demo hippies */
	        if (conf('demo_mode')) { break; }
		
		$nexturl = conf('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
		show_confirmation(_('Delete Catalog'),_('Do you really want to delete this catalog?'),$nexturl,1);
	break;
	case 'show_customize_catalog':
		include(conf('prefix') . '/templates/show_edit_catalog.inc.php');
	break;
	case 'gather_album_art':
	        flush();

		$catalogs = $catalog->get_catalogs();
		foreach ($catalogs as $data) { 
	        	echo "<div class=\"confirmation-box\"><b>" . _("Starting Album Art Search") . ". . .</b><br /><br />\n";
			echo _('Searched') . ": <span id=\"count_art_" . $data->id . "\">" . _('None') . "</span><br />";
			$data->get_album_art();
			echo "<b>" . _("Album Art Search Finished") . ". . .</b></div>\n";
		}
		$url 	= conf('web_path') . '/admin/index.php';
		$title 	= _('Album Art Search Finished');
		$body	= '';
		show_confirmation($title,$body,$url);
        break;
	default:
		/* Not sure what to put here anymore */
	break;
} // end switch

/* Show the Footer */
show_footer();

?>
