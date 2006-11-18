<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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


require('lib/init.php');

/* If we are running a demo, quick while you still can! */
if (conf('demo_mode')) {
        exit();
}

$web_path = conf('web_path');

if($GLOBALS['user']->prefs['localplay_level'] < 1) {
	access_denied();
	exit();
}

/* Scrub in the action */
$action = scrub_in($_REQUEST['action']);

show_template('header');


switch ($action) { 
	case 'delete_song':
		$song_id = scrub_in($_REQUEST['song_id']);
		$songs = array($song_id);
		$localplay = init_localplay(); 
		$localplay->delete($songs);
		$url 	= $web_path . '/localplay.php';
		$title	= _('Song(s) Removed from Playlist'); 
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'delete_all':
		$localplay = init_localplay(); 
		$localplay->delete_all();
		$url	= $web_path . '/localplay.php';
		$title	= _('Song(s) Removed from Playlist');
		$body	= '';
		show_confirmation($title,$body,$url);
	break;
	case 'repeat':
		$localplay = init_localplay(); 
		$localplay->repeat(make_bool($_REQUEST['value']));
		require_once (conf('prefix') . '/templates/show_localplay.inc.php');
	break;
	case 'random':
		$localplay = init_localplay(); 
		$localplay->random(make_bool($_REQUEST['value']));
		require_once (conf('prefix') . '/templates/show_localplay.inc.php');
	break;
	default: 
		if ($localplay = init_localplay()) { 
			require_once (conf('prefix') . '/templates/show_localplay.inc.php');
		} 
		else { 
			$GLOBALS['error']->add_error('general',_('Localplay Init Failed'));
			$GLOBALS['error']->print_error('general');
		}
	break;
} // end switch action



show_footer();

?>
