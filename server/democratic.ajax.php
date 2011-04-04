<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Democratic Ajax
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) { exit; }

$democratic = Democratic::get_current_playlist();
$democratic->set_parent();

switch ($_REQUEST['action']) {
	case 'delete_vote':
		$democratic->remove_vote($_REQUEST['row_id']);
		$show_browse = true;
	break;
	case 'add_vote':
		$democratic->add_vote($_REQUEST['object_id'],$_REQUEST['type']);
		$show_browse = true;
	break;
	case 'delete':
		if (!$GLOBALS['user']->has_access('75')) {
			echo xml_from_array(array('rfc3514' => '0x1'));
			exit;
		}

		$democratic->delete_votes($_REQUEST['row_id']);
		$show_browse = true;
	break;
	case 'send_playlist':
		if (!Access::check('interface','75')) {
			echo xml_from_array(array('rfc3514' => '0x1'));
			exit;
		}

		$_SESSION['iframe']['target'] = Config::get('web_path') . '/stream.php?action=democratic&democratic_id=' . scrub_out($_REQUEST['democratic_id']);
		$results['rfc3514'] = '<script type="text/javascript">reloadUtil("'.$_SESSION['iframe']['target'].'")</script>';
	break;
	case 'clear_playlist':
		if (!Access::check('interface','100')) {
			echo xml_from_array(array('rfc3514' => '0x1'));
			exit;
		}

		$democratic = new Democratic($_REQUEST['democratic_id']);
		$democratic->set_parent();
		$democratic->clear();

		$show_browse = true;
	break;
	default:
		$results['rfc3514'] = '0x1';
	break;
} // switch on action;

if ($show_browse) {
	ob_start();
	$object_ids = $democratic->get_items();
	$browse = new Browse();
	$browse->set_type('democratic');
	$browse->set_static_content(true);
	$browse->show_objects($object_ids);
	$browse->store();
	$results['browse_content'] = ob_get_contents();
	ob_end_clean();
}

// We always do this
echo xml_from_array($results);
?>
