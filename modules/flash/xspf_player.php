<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * XSPF Player
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

require_once '../../lib/init.php';

// Switch on actions
switch ($_REQUEST['action']) {
	default:
	case 'tmp_playlist':
		// Set for hackage!
		$_REQUEST['flash_hack'] = 1;
		$objects = $GLOBALS['user']->playlist->get_items();
		$stream = new Stream_Playlist();
		$stream->add($objects);
		$stream->generate_playlist('xspf', false);
	break;
	case 'show':
		$play_url = Config::get('web_path') . '/modules/flash/xspf_player.php';
		require_once Config::get('prefix') . '/templates/show_xspf_player.inc.php';
	break;
} // end switch


?>
