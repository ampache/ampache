<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
	@header Flags Mojo
*/

require_once ("../modules/init.php");
require_once( conf('prefix').'/lib/flag.php');

if (!$user->has_access(100)) { 
	header ("Location: " . conf('web_path') . "/index.php?access=denied");
	exit();
}


$action = scrub_in($_REQUEST['action']);
show_template('header');

show_menu_items('Admin');
show_admin_menu('Catalog');

switch ($action)
{
    case 'show':
        $flags = get_flagged_songs();
        show_flagged_songs($flags);
        break;
    case 'Set Flags':
    case 'Update Flags':
        $flags = scrub_in($_REQUEST['song']);
        update_flags($flags);
        $newflags = get_flagged_songs();
        show_flagged_songs($newflags);
        break;
    case 'Edit Selected':
        $flags = scrub_in($_REQUEST['song']);
        $count = add_to_edit_queue($flags);
        if($count) show_edit_flagged();
        break;
    case 'Next':
        $song = scrub_in($_REQUEST['song']);
        update_song_info($song);
        show_edit_flagged();
        // Pull song ids from an edit queue in $_SESSION,
        // And edit them one at a time
        break;
    case 'Skip':
        $count = add_to_edit_queue(scrub_in($_REQUEST['song']));
        if($count) show_edit_flagged();
    case 'Flag Songs':
        break;
    case 'Remove Flags':
        break;
    case 'Clear Edit List':
        unset($_SESSION['edit_queue']);

    case 'Done':
        $song = scrub_in($_REQUEST['song']);
        update_song_info($song);
    default:
        $flags = get_flagged_songs();
        show_flagged_songs($flags);

}

show_footer();
show_page_footer ('Admin', 'Catalog',$user->prefs['display_menu']);

?>
