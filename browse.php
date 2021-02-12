<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Browse Page
 * This page shows the browse menu, which allows you to browse by many different
 * fields including artist, album, and catalog.
 *
 * This page also handles the actual browse action
 */

/* Base Require */
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

session_start();

// This page is a little wonky we don't want the sidebar until we know what
// type we're dealing with so we've got a little switch here that creates the
// type.. this feels hackish...
$browse = new Browse();
switch ($_REQUEST['action']) {
    case 'tag':
    case 'file':
    case 'album':
    case 'artist':
    case 'playlist':
    case 'smartplaylist':
    case 'live_stream':
    case 'video':
    case 'song':
    case 'channel':
    case 'broadcast':
    case 'tvshow':
    case 'tvshow_season':
    case 'tvshow_episode':
    case 'movie':
    case 'clip':
    case 'personal_video':
    case 'label':
    case 'pvmsg':
    case 'podcast':
    case 'podcast_episode':
        $browse->set_type(Core::get_request('action'));
        $browse->set_simple_browse(true);
        break;
    case 'album_artist':
        $browse->set_type('artist');
        $browse->set_simple_browse(true);
        break;
} // end switch

UI::show_header();

if (in_array($_REQUEST['action'], array('song', 'album', 'album_artist', 'label', 'channel', 'broadcast', 'live_stream', 'podcast', 'video'))) {
    UI::show('show_browse_form.inc.php');
}

// Browser is able to save page on current session. Only applied to main menus.
$browse->set_update_session(true);

switch ($_REQUEST['action']) {
    case 'album':
        $browse->set_filter('catalog', $_SESSION['catalog']);
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('name', 'ASC');
        $browse->update_browse_from_session();  // Update current index depending on what is in session.
        $browse->show_objects();
        break;
    case 'tag':
        // FIXME: This whole thing is ugly, even though it works.
        $browse->set_sort('count', 'ASC');
        // This one's a doozy
        $browse_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'artist';
        $browse->set_simple_browse(false);
        $browse->save_objects(Tag::get_tags($browse_type, 0, 'name')); // Should add a pager?
        $object_ids = $browse->get_saved();
        $keys       = array_keys($object_ids);
        Tag::build_cache($keys);
        UI::show_box_top(T_('Tag Cloud'), 'box box_tag_cloud');
        $browse2 = new Browse();
        $browse2->set_type($browse_type);
        $browse2->store();
        require_once AmpConfig::get('prefix') . UI::find_template('show_tagcloud.inc.php');
        UI::show_box_bottom();
        $type = $browse2->get_type();
        require_once AmpConfig::get('prefix') . UI::find_template('browse_content.inc.php');
        break;
    case 'artist':
        $browse->set_filter('catalog', $_SESSION['catalog']);
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('name', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'album_artist':
        $browse->set_filter('catalog', $_SESSION['catalog']);
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_filter('album_artist', true);
        $browse->set_sort('name', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'song':
        $browse->set_filter('catalog', $_SESSION['catalog']);
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('title', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'live_stream':
    case 'tvshow':
    case 'label':
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('name', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'playlist':
        $browse->set_sort('name', 'ASC');
        $browse->set_sort('last_update', 'DESC');
        $browse->set_filter('playlist_type', '1');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'smartplaylist':
        $browse->set_sort('name', 'ASC');
        $browse->set_filter('playlist_type', '1');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'channel':
    case 'broadcast':
        $browse->set_sort('id', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'video':
    case 'podcast':
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('title', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'tvshow_season':
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('season_number', 'ASC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'tvshow_episode':
    case 'movie':
    case 'clip':
    case 'personal_video':
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'pvmsg':
        $browse->set_sort('creation_date', 'DESC');
        $folder = $_REQUEST['folder'];
        if ($folder === "sent") {
            $browse->set_filter('user', Core::get_global('user')->id);
        } else {
            $browse->set_filter('to_user', Core::get_global('user')->id);
        }
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'podcast_episode':
        if (AmpConfig::get('catalog_disable')) {
            $browse->set_filter('catalog_enabled', '1');
        }
        $browse->set_sort('pubdate', 'DESC');
        $browse->update_browse_from_session();
        $browse->show_objects();
        break;
    case 'file':
    case 'catalog':
    default:
        break;
} // end Switch $action

$browse->store();

// Show the Footer
UI::show_query_stats();
UI::show_footer();
