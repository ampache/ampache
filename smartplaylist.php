<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

require_once 'lib/init.php';

// We special-case this so we can send a 302 if the delete succeeded
if ($_REQUEST['action'] == 'delete_playlist') {
	// Check rights
	$playlist = new Search('song', $_REQUEST['playlist_id']);
	if ($playlist->has_access()) {
		$playlist->delete();
		// Go elsewhere
		header('Location: ' . Config::get('web_path') . '/browse.php?action=smartplaylist');
	}
}

show_header();

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
	case 'create_playlist':
		/* Check rights */
		if (!Access::check('interface','25')) {
			access_denied();
			break;
		}

		foreach ($_REQUEST as $key => $value) {
			$prefix = substr($key, 0, 4);
			$value = trim($value);

			if ($prefix == 'rule' && strlen($value)) {
				$rules[$key] = Dba::escape($value);
			}
		}

		switch($_REQUEST['operator']) {
			case 'or':
				$operator = 'OR';
			break;
			default:
				$operator = 'AND';
			break;
		} // end switch on operator

		$playlist_name	= scrub_in($_REQUEST['playlist_name']);

		$playlist = new Search('song');
		$playlist->parse_rules($data);
		$playlist->logic_operator = $operator;
		$playlist->name = $playlist_name;
		$playlist->save();
		
	break;
	case 'delete_playlist':
		// If we made it here, we didn't have sufficient rights.
		access_denied();
	break;
	case 'show_playlist':
		$playlist = new Search('song', $_REQUEST['playlist_id']);
		$playlist->format();
		require_once Config::get('prefix') . '/templates/show_smartplaylist.inc.php';
	break;
	case 'update_playlist':
		$playlist = new Search('song', $_REQUEST['playlist_id']);
		if ($playlist->has_access()) {
			$playlist->parse_rules(Search::clean_request($_REQUEST));
			$playlist->update();
			$playlist->format();
		}
		else {
			access_denied();
			break;
		}
		require_once Config::get('prefix') . '/templates/show_smartplaylist.inc.php';
	break;
	default:
		require_once Config::get('prefix') . '/templates/show_smartplaylist.inc.php';
	break;
} // switch on the action

show_footer();
?>
