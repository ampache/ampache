<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
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

require_once 'lib/init.php';
ob_end_clean();

//test that batch download is permitted
if (!Access::check_function('batch_download')) {
    UI::access_denied();
    exit;
}

/* Drop the normal Time limit constraints, this can take a while */
set_time_limit(0);

switch ($_REQUEST['action']) {
    case 'tmp_playlist':
        $media_ids = $GLOBALS['user']->playlist->get_items();
        $name = $GLOBALS['user']->username . ' - Playlist';
    break;
    case 'playlist':
        $playlist = new Playlist($_REQUEST['id']);
        $media_ids = $playlist->get_songs();
        $name = $playlist->name;
    break;
    case 'smartplaylist':
        $search = new Search('song', $_REQUEST['id']);
        $sql = $search->to_sql();
        $sql = $sql['base'] . ' ' . $sql['table_sql'] . ' WHERE ' . 
            $sql['where_sql'];
        $db_results = Dba::read($sql);
        $media_ids = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $media_ids[] = $row['id'];
        }
        $name = $search->name;
    break;
    case 'album':
        $album = new Album($_REQUEST['id']);
        $media_ids = $album->get_songs();
        $name = $album->name;
    break;
    case 'artist':
        $artist = new Artist($_REQUEST['id']);
        $media_ids = $artist->get_songs();
        $name = $artist->name;
    break;
    case 'browse':
        $id = scrub_in($_REQUEST['browse_id']);
        $browse = new Browse($id);
        $browse_media_ids = $browse->get_saved();
        $media_ids = array();
        foreach ($browse_media_ids as $media_id) {
            switch ($_REQUEST['type']) {
                case 'album':
                    $album = new Album($media_id);
                    $media_ids = array_merge($media_ids, $album->get_songs());
                break;
                case 'song':
                    $media_ids[] = $media_id;
                break;
                case 'video':
                    $media_ids[] = array('Video', $media_id);
                break;
            } // switch on type
        } // foreach media_id
        $name = 'Batch-' . date("dmY",time());
    default:
        // Rien a faire
    break;
} // action switch

// Take whatever we've got and send the zip
$song_files = get_song_files($media_ids);
set_memory_limit($song_files['1']+32);
send_zip($name,$song_files['0']);
exit;
?>
