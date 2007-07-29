<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

require_once '../../lib/init.php';

$web_path = Config::get('web_path');

/* Attempt to build the temp playlist */
$action 	= scrub_in($_REQUEST['action']);

switch ($action) { 
	default:
	case 'tmp_playlist':
		// Set for hackage!
		$_REQUEST['flash_hack'] = 1;
		$tmp_playlist = new tmpPlaylist($_REQUEST['tmp_id']);
		$objects = $tmp_playlist->get_items();

                //Recurse through the objects
                foreach ($objects as $object_data) {
                        // Switch on the type of object we've got in here
                        switch ($object_data['1']) {
                                case 'radio':
                                        $radio = new Radio($object_data['0']);
                                        $urls[] = $radio->url;
                                        $song_ids[] = '-1';
                                break;
                                case 'song':
                                        $song_ids[] = $object_data['0'];
                                break;
                                default:
                                        $random_url = Random::play_url($object_data['1']);
                                        // If there's something to actually add
                                        if ($random_url) {
                                                $urls[] = $random_url;
                                                $song_ids[] = '-1';
                                        }
                                break;
                        } // end switch on type
                } // end foreach
		$stream = new Stream('xspf',$song_ids);
                if (is_array($urls)) {
                        foreach ($urls as $url) {
                                $stream->manual_url_add($url);
                        }
                }
		$stream->start();
	break;
	case 'show':
		$play_url = Config::get('web_path') . '/modules/flash/xspf_player.php?tmp_id=' . scrub_out($_REQUEST['tmpplaylist_id']);
		require_once Config::get('prefix') . '/templates/show_xspf_player.inc.php';
	break;
} // end switch


?>
