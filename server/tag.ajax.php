<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

switch ($_REQUEST['action']) {
	case 'show_add_tag':

	break;
	case 'add_tag':
		Tag::add_tag_map($_GET['type'],$_GET['object_id'],$_GET['tag_id']);
	break;
	case 'remove_tag':
		$tag = new Tag($_GET['tag_id']);
		$tag->remove_map($_GET['type'],$_GET['object_id']);
	break;
	case 'browse_type':
		$browse = new Browse($_GET['browse_id']);
		$browse->set_filter('object_type', $_GET['type']);
		$browse->store();
	break;
	case 'add_filter':
		$browse = new Browse($_GET['browse_id']);
		$browse->set_filter('tag', $_GET['tag_id']);
		$object_ids = $browse->get_objects();
		ob_start();
		$browse->show_objects($object_ids);
		$results['browse_content'] = ob_get_clean();
		$browse->store();
		// Retrieve current objects of type based on combined filters
	break;
	default:
		$results['rfc3514'] = '0x1';
	break;
} // switch on action;


// We always do this
echo xml_from_array($results);
?>
