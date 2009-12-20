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


require_once '../lib/init.php';

if (!Access::check('interface','100')) {
	access_denied();
	exit; 
}

show_header(); 

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
		toggle_visible('ajax-loading'); 
		ob_end_flush(); 
	    	if (Config::get('demo_mode')) { break; }
		if ($_REQUEST['catalogs'] ) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				$catalog = new Catalog($catalog_id);
				$catalog->add_to_catalog();
			}
	       	}
		$url 	= Config::get('web_path') . '/admin/catalog.php';
		$title 	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
		toggle_visible('ajax-loading'); 
	break;
	case 'update_all_catalogs':
		$_REQUEST['catalogs'] = Catalog::get_catalog_ids();
	case 'update_catalog':
		toggle_visible('ajax-loading'); 
		ob_end_flush(); 
	    	/* If they are in demo mode stop here */
	        if (Config::get('demo_mode')) { break; }

		if (isset($_REQUEST['catalogs'])) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				$catalog = new Catalog($catalog_id); 
				$catalog->verify_catalog($catalog_id);
			}
		}
		$url	= Config::get('web_path') . '/admin/catalog.php';
		$title	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
		toggle_visible('ajax-loading'); 
	break;
	case 'full_service':
		toggle_visible('ajax-loading'); 
		ob_end_flush(); 
		/* Make sure they aren't in demo mode */
		if (Config::get('demo_mode')) { access_denied(); break; } 

		if (!$_REQUEST['catalogs']) { 
			$_REQUEST['catalogs'] = Catalog::get_catalog_ids();
		}

		/* This runs the clean/verify/add in that order */
		foreach ($_REQUEST['catalogs'] as $catalog_id) { 
			$catalog = new Catalog($catalog_id);
			$catalog->clean_catalog($catalog_id);
			$catalog->count = 0;
			$catalog->verify_catalog($catalog_id);
			$catalog->count = 0;
			$catalog->add_to_catalog($catalog_id);
		} 		
		$url	= Config::get('web_path') . '/admin/catalog.php';
		$title	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
		toggle_visible('ajax-loading'); 
	break;
	case 'delete_catalog':
		/* Make sure they aren't in demo mode */
	        if (Config::get('demo_mode')) { break; }

		if (!Core::form_verify('delete_catalog')) { 
			access_denied(); 
			exit; 
		} 
	
		/* Delete the sucker, we don't need to check perms as thats done above */
		Catalog::delete($_GET['catalog_id']); 
		$next_url = Config::get('web_path') . '/admin/catalog.php';
		show_confirmation(_('Catalog Deleted'),_('The Catalog and all associated records have been deleted'),$nexturl);
	break;
	case 'show_delete_catalog': 
		$catalog_id = scrub_in($_GET['catalog_id']); 

		$next_url = Config::get('web_path') . '/admin/catalog.php?action=delete_catalog&catalog_id=' . scrub_out($catalog_id); 
		show_confirmation(_('Catalog Delete'),_('Confirm Deletion Request'),$next_url,1,'delete_catalog'); 
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
		$url	= conf('web_path') . '/admin/catalog.php';
		$title	= _('Disabled Songs Processed');
		show_confirmation($title,$body,$url);
	break;
	case 'clean_all_catalogs':
		$catalog = new Catalog(); 
		$_REQUEST['catalogs'] = Catalog::get_catalog_ids();
	case 'clean_catalog':
		toggle_visible('ajax-loading'); 
		ob_end_flush(); 
	    	/* If they are in demo mode stop them here */
	        if (Config::get('demo_mode')) { break; }
	
		// Make sure they checked something
		if (isset($_REQUEST['catalogs'])) {	
			foreach($_REQUEST['catalogs'] as $catalog_id) { 
				$catalog = new Catalog($catalog_id);
				$catalog->clean_catalog(0,1);
			} // end foreach catalogs
		}
		
		$url 	= Config::get('web_path') . '/admin/catalog.php';
		$title	= _('Catalog Cleaned');
		$body	= '';
		show_confirmation($title,$body,$url);
		toggle_visible('ajax-loading'); 
	break;
	case 'update_catalog_settings':
		/* No Demo Here! */
        	if (Config::get('demo_mode')) { break; }

		/* Update the catalog */
		Catalog::update_settings($_REQUEST);
		
		$url 	= Config::get('web_path') . '/admin/catalog.php';
		$title 	= _('Catalog Updated');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'update_from': 
		if (Config::get('demo_mode')) { break; } 

		// First see if we need to do an add
		if ($_POST['add_path'] != '/' AND strlen($_POST['add_path'])) { 
			if ($catalog_id = Catalog::get_from_path($_POST['add_path'])) { 
				$catalog = new Catalog($catalog_id); 
				$catalog->run_add(array('subdirectory'=>$_POST['add_path'])); 
			} 
		} // end if add
		
		// Now check for an update
		if ($_POST['update_path'] != '/' AND strlen($_POST['update_path'])) { 
			if ($catalog_id = Catalog::get_from_path($_POST['update_path'])) { 
				$songs = Song::get_from_path($_POST['update_path']); 
				foreach ($songs as $song_id) { Catalog::update_single_item('song',$song_id); } 
			} 
		} // end if update

	break; 
	case 'add_catalog':
		/* Wah Demo! */
		if (Config::get('demo_mode')) { break; }

		ob_end_flush(); 

		if (!strlen($_POST['path']) || !strlen($_POST['name'])) { 
			Error::add('general','Error Name and path not specified'); 
		} 

		if (substr($_POST['path'],0,7) != 'http://' && $_POST['type'] == 'remote') { 
			Error::add('general','Error Remote selected, but path is not a URL'); 
		} 
		
		if ($_POST['type'] == 'remote' && !strlen($_POST['key'])) { 
			Error::add('general','Error Remote Catalog specified, but no key provided'); 
		} 

		if (!Core::form_verify('add_catalog','post')) { 
			access_denied(); 
			exit; 
		} 

		// Make sure that there isn't a catalog with a directory above this one
		if (Catalog::get_from_path($_POST['path'])) { 
			Error::add('general',_('Error: Defined Path is inside an existing catalog')); 
		} 

		// If an error hasn't occured
		if (!Error::occurred()) { 

			$catalog_id = Catalog::Create($_POST); 

			if (!$catalog_id) { 
				require Config::get('prefix') . '/templates/show_add_catalog.inc.php'; 
				break; 
			} 

			$catalog = new Catalog($catalog_id); 
			
			// Run our initial add
			$catalog->run_add($_POST); 

			show_box_top(); 
			echo "<h2>" .  _('Catalog Created') . "</h2>";
			Error::display('general'); 
			Error::display('catalog_add'); 
			show_box_bottom(); 

			show_confirmation('','','/admin/catalog.php'); 

		}
		else {
			require Config::get('prefix') . '/templates/show_add_catalog.inc.php';
		}
	break;
	case 'clear_stats':
    		if (Config::get('demo_mode')) { access_denied(); break; }
		
		Catalog::clear_stats(); 
		$url	= Config::get('web_path') . '/admin/catalog.php';
		$title	= _('Catalog statistics cleared');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	default:
	case 'show_catalogs': 
		require_once Config::get('prefix') . '/templates/show_manage_catalogs.inc.php'; 
	break;
	case 'show_add_catalog':
		require Config::get('prefix') . '/templates/show_add_catalog.inc.php';
	break;
	case 'clear_now_playing':
	        if (Config::get('demo_mode')) { access_denied(); break; }
	    	Stream::clear_now_playing();
		show_confirmation(_('Now Playing Cleared'),_('All now playing data has been cleared'),Config::get('web_path') . '/admin/catalog.php');
	break;
	case 'show_disabled':
	        if (conf('demo_mode')) { break; }
		
		$songs = $catalog->get_disabled();
		if (count($songs)) { 
			require (conf('prefix') . '/templates/show_disabled_songs.inc.php');
		}
		else {
			echo "<div class=\"error\" align=\"center\">" . _('No Disabled songs found') . "</div>";
		}
	break;
	case 'show_delete_catalog':
		/* Stop the demo hippies */
	        if (Config::get('demo_mode')) { access_denied(); break; } 

		$catalog = new Catalog($_REQUEST['catalog_id']); 	
		$nexturl = Config::get('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
		show_confirmation(_('Delete Catalog'),_('Do you really want to delete this catalog?') . " -- $catalog->name ($catalog->path)",$nexturl,1);
	break;
	case 'show_customize_catalog':
		$catalog = new Catalog($_REQUEST['catalog_id']); 
		require_once Config::get('prefix') . '/templates/show_edit_catalog.inc.php';
	break;
	case 'gather_album_art':
		toggle_visible('ajax-loading'); 
		ob_end_flush(); 

		$catalogs = $_REQUEST['catalogs'] ? $_REQUEST['catalogs'] : Catalog::get_catalogs();

		// Itterate throught the catalogs and gather as needed
		foreach ($catalogs as $catalog_id) { 
			$catalog = new Catalog($catalog_id);
			require Config::get('prefix') . '/templates/show_gather_art.inc.php'; 
			flush(); 
			$catalog->get_album_art('',1);
		}
		$url 	= Config::get('web_path') . '/admin/catalog.php';
		$title 	= _('Album Art Search Finished');
		$body	= '';
		show_confirmation($title,$body,$url);
        break;
} // end switch

/* Show the Footer */
show_footer();

?>
