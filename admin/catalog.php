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

require('../modules/init.php');

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
	case _("Add to Catalog(s)"):
	case 'add_to_catalog':
	    	if (conf('demo_mode')) { break; }
		if ($_REQUEST['catalogs'] ) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				$catalog = new Catalog($catalog_id);
				$catalog->add_to_catalog($_REQUEST['update_type']);
			}
	       	}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case _("Add to all Catalogs"):
		if (conf('demo_mode')) { break; }
	
		/* If they are using the file MPD type, and it's currently enabled lets do a DBRefresh for em */
		if (conf('mpd_method') == 'file' AND conf('allow_mpd_playback')) {
			// Connect to the MPD
			if (!class_exists('mpd')) { require_once(conf('prefix') . "/modules/mpd/mpd.class.php"); }
			if (!is_object($myMpd)) { $myMpd = new mpd(conf('mpd_host'),conf('mpd_port')); }
			if (!$myMpd->connected) {
				echo "<font class=\"error\">" . _("Error Connecting") . ": " . $myMpd->errStr . "</font>\n";
				log_event($_SESSION['userdata']['username'],' connection_failed ',"Error: Unable able to connect to MPD, " . $myMpd->errStr);
			} // MPD connect failed
			
		 $myMpd->DBRefresh();
		} // if MPD enabled
		$catalogs = $catalog->get_catalogs();

		foreach ($catalogs as $data) { 
			$data->add_to_catalog($_REQUEST['update_type']);
		}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case _("Update Catalog(s)"):
	case 'update_catalog':
	    	/* If they are in demo mode stop here */
	        if (conf('demo_mode')) { break; }

		if (isset($_REQUEST['catalogs'])) {
			foreach ($_REQUEST['catalogs'] as $catalog_id) {
				$catalog = new Catalog($catalog_id);
				$catalog->verify_catalog($catalog_id->id,$_REQUEST['update_type']);
			}
		}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case _("Update All Catalogs"):
	        if (conf('demo_mode')) { break; }
		$catalogs = $catalog->get_catalogs();

		foreach ($catalogs as $data) {
			$data->verify_catalog($data->id,$_REQUEST['update_type']);
		}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case 'full_service':
		/* Make sure they aren't in demo mode */
		if (conf('demo_mode')) { break; } 

		if (!$_REQUEST['catalogs']) { 
			$_REQUEST['catalogs'] = array();
			$catalogs = Catalog::get_catalogs();
		}

		/* This runs the clean/verify/add in that order */
		foreach ($_REQUEST['catalogs'] as $catalog_id) { 
			$catalog = new Catalog($catalog_id);
			$catalogs[] = $catalog;
		}

		foreach ($catalogs as $catalog) { 
			$catalog->clean_catalog();
			$catalog->count = 0;
			$catalog->verify_catalog();
			$catalog->count = 0;
			$catalog->add_to_catalog();
		} 		
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
			echo "<p align=\"center\">Songs Removed... </p>";
		}
		else {
			echo "<p align=\"center\">No Songs Removed... </p>";
		}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case _('Clean Catalog(s)'):
	case 'clean_catalog':
	    	/* If they are in demo mode stop them here */
	        if (conf('demo_mode')) { break; }
	
		// Make sure they checked something
		if (isset($_REQUEST['catalogs'])) {	
			foreach($_REQUEST['catalogs'] as $catalog_id) { 
				$catalog = new Catalog($catalog_id);
				$catalog->clean_catalog(0,1);
			} // end foreach catalogs
		}
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case 'update_catalog_settings':
        	if (conf('demo_mode')) { break; }
		$id 	= strip_tags($_REQUEST['catalog_id']);
		$name 	= strip_tags($_REQUEST['name']);
		$id3cmd = strip_tags($_REQUEST['id3_set_command']);
		$rename = strip_tags($_REQUEST['rename_pattern']);
		$sort 	= strip_tags($_REQUEST['sort_pattern']);
		/* Setup SQL */
		$sql = "UPDATE catalog SET " .
			" name = '$name'," .
			" id3_set_command = '$id3cmd'," .
			" rename_pattern = '$rename'," .
			" sort_pattern = '$sort'" .
			" WHERE id = '$id'";
		$result = mysql_query($sql, dbh());
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	case _("Clean All Catalogs"):
	        if (conf('demo_mode')) { break; }
		$catalogs = $catalog->get_catalogs();
		$dead_files = array();	
	
		foreach ($catalogs as $catalog) {
			$catalog->clean_catalog(0,$_REQUEST['update_type']);
		}
	
		include(conf('prefix') . '/templates/catalog.inc');
	break;
	// FIXME!
	case 'add_catalog':
		/* Wah Demo! */
		if (conf('demo_mode')) { break; }
		
		if ($_REQUEST['path'] AND $_REQUEST['name']) { 
			/* Throw all of the album art types into an array */
			$art = array('id3'=>$_REQUEST['art_id3v2'],'amazon'=>$_REQUEST['art_amazon'],'folder'=>$_REQUEST['art_folder']);
			/* Create the Catalog */
			$catalog->new_catalog($_REQUEST['path'],
					$_REQUEST['name'],
					$_REQUEST['id3set_command'],
					$_REQUEST['rename_pattern'],
					$_REQUEST['sort_pattern'],
					$_REQUEST['type'],
					$_REQUEST['gather_art'],
					$_REQUEST['parse_m3u'],
					$art);
			$url = conf('web_path') . '/admin/index.php';
			$title = _('Catalog Created');
			$body  = _('Catalog Created and Songs Indexed');
			show_confirmation($title,$body,$url);
		}
		else {
			$error = "Please complete the form.";
			include(conf('prefix') . '/templates/add_catalog.inc');
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
		include(conf('prefix') . '/templates/add_catalog.inc');
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
			echo "<p class=\"error\" align=\"center\">No Disabled songs found</p>";
		}
	break;
	case 'show_delete_catalog':
		/* Stop the demo hippies */
	        if (conf('demo_mode')) { break; }
		
		$nexturl = conf('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
		show_confirmation(_('Delete Catalog'),_('Do you really want to delete this catalog?'),$nexturl,1);
	break;
	case 'show_flagged_songs':
	        if (conf('demo_mode')) { break; }
		$type = $_REQUEST['action'];
		include (conf('prefix') . '/templates/flag.inc');
	break;
	case 'show_customize_catalog':
		include(conf('prefix') . '/templates/customize_catalog.inc');
	break;
	case 'gather_album_art':
	        echo "<b>" . _("Starting Album Art Search") . ". . .</b><br /><br />\n";
	        flush();

		$catalogs = $catalog->get_catalogs();
		foreach ($catalogs as $data) { 
			$data->get_album_art();
		}

		echo "<b>" . _("Album Art Search Finished") . ". . .</b><br />\n";

        break;
	default:
		include(conf('prefix') . '/templates/catalog.inc');
	break;
} // end switch

/* Show the Footer */
show_footer();

?>
