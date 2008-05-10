<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

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

/**
 * Flag Document
 * This is called for all of our flagging needs
 */


require_once('lib/init.php');

show_template('header');

$action = scrub_in($_REQUEST['action']);
$flag = new Flag($_REQUEST['flag_id']);

/* Switch on the action */
switch ($action) { 
	case 'remove_flag':
	break;
	case 'flag':
		$id 		= scrub_in($_REQUEST['id']);
		$type		= scrub_in($_REQUEST['type']);
		$flag_type	= scrub_in($_REQUEST['flag_type']);
		$comment	= scrub_in($_REQUEST['comment']);
		$flag->add($id,$type,$flag_type,$comment);		
		show_confirmation(_('Item Flagged'),_('The specified item has been flagged'),$_SESSION['source_page']);
	break;
	case 'show_flag':
		/* Store where they came from */
		$_SESSION['source_page'] = return_referer();
		include(conf('prefix') . '/templates/show_flag.inc.php');
	break;
	case 'show_remove_flag':
	
	break;
	default:
	
	break;
} // end action switch

show_footer();
?>
