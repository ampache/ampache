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

require('../lib/init.php');


/* Scrub in the Needed vars */
$action = scrub_in($_REQUEST['action']);
$access_id = scrub_in($_REQUEST['access_id']);
$access = new Access($access_id);

if (!$GLOBALS['user']->has_access(100) || conf('demo_mode')) { 
	access_denied();
	exit();
}


show_template('header');


switch ($action ) { 
	case 'show_confirm_delete':
		$title 	= _('Confirm Delete');
		$body	= _('Do you really want to delete this Access Record?');
		show_confirmation($title,$body,'admin/access.php?access_id=' . scrub_out($_REQUEST['access_id']) . '&amp;action=delete_host','1');
	break;
	case 'delete_host':
		$access->delete($_REQUEST['access_id']);
		$url = conf('web_path') . '/admin/access.php';
		show_confirmation(_('Entry Deleted'),_('Your Access List Entry has been removed'),$url);
	break;
	case 'add_host':
		$access->create($_REQUEST['name'],$_REQUEST['start'],$_REQUEST['end'],$_REQUEST['level'],$_REQUEST['user'],$_REQUEST['key'],$_REQUEST['type']);
		$url = conf('web_path') . '/admin/access.php';
		show_confirmation(_('Entry Added'),_('Your new Access List Entry has been created'),$url);
	break;
	case 'update_host':
		$access->update($_REQUEST);
		show_confirmation(_('Entry Updated'),_('Access List Entry updated'),'admin/access.php');
	break;
	case 'show_add_host':
		include(conf('prefix') . '/templates/show_add_access.inc');
	break;
	case 'show_edit_host':
		include(conf('prefix') . '/templates/show_edit_access.inc');
	break;
	default:
		$list = array();
		$list = $access->get_access_list();
		include(conf('prefix') .'/templates/show_access_list.inc');
	break;
} // end switch on action
show_footer();
?>
