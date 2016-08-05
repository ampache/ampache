<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once '../lib/init.php';
require_once AmpConfig::get('prefix') . '/modules/catalog/local/local.catalog.php';

if (!Access::check('interface', '100')) {
    UI::access_denied();
    exit;
}

UI::show_header();

$catalogs = $_REQUEST['catalogs'];
// If only one catalog, check it is ready.
if (is_array($catalogs) && count($catalogs) == 1 && $_REQUEST['action'] !== 'delete_catalog' && $_REQUEST['action'] !== 'show_delete_catalog') {
    // If not ready, display the data to make it ready / stop the action.
    $catalog = Catalog::create_from_id($catalogs[0]);
    if (!$catalog->isReady()) {
        if (!isset($_REQUEST['perform_ready'])) {
            $catalog->show_ready_process();
            UI::show_footer();
            exit;
        } else {
            $catalog->perform_ready();
        }
    }
}


/* Big switch statement to handle various actions */
switch ($_REQUEST['action']) {
    case 'add_to_all_catalogs':
        catalog_worker('add_to_all_catalogs');
        show_confirmation(T_('Catalog Update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'add_to_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        catalog_worker('add_to_catalog', $catalogs);
        show_confirmation(T_('Catalog Update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'update_all_catalogs':
        catalog_worker('update_all_catalogs');
        show_confirmation(T_('Catalog Update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'update_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        catalog_worker('update_catalog', $catalogs);
        show_confirmation(T_('Catalog Update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'full_service':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }

        catalog_worker('full_service', $catalogs);
        show_confirmation(T_('Catalog Update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'delete_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('delete_catalog')) {
            UI::access_denied();
            exit;
        }

        $deleted = true;
        /* Delete the sucker, we don't need to check perms as thats done above */
        foreach ($catalogs as $catalog_id) {
            $deleted = Catalog::delete($catalog_id);
            if (!$deleted) {
                break;
            }
        }
        $next_url = AmpConfig::get('web_path') . '/admin/catalog.php';
        if ($deleted) {
            show_confirmation(T_('Catalog Deleted'), T_('The Catalog and all associated records have been deleted'), $next_url);
        } else {
            show_confirmation(T_('Error'), T_('Cannot delete the catalog'), $next_url);
        }
    break;
    case 'show_delete_catalog':
        $next_url = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&catalogs[]=' . implode(',', $catalogs);
        show_confirmation(T_('Catalog Delete'), T_('Confirm Deletion Request'), $next_url, 1, 'delete_catalog');
    break;
    case 'enable_disabled':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $songs = $_REQUEST['song'];

        if (count($songs)) {
            foreach ($songs as $song_id) {
                Song::update_enabled(true, $song_id);
            }
            $body = count($songs) . nT_(' Song Enabled', ' Songs Enabled', count($songs));
        } else {
            $body = T_('No Disabled Songs selected');
        }
        $url      = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = count($songs) . nT_(' Disabled Song Processed', ' Disabled Songs Processed', count($songs));
        show_confirmation($title, $body, $url);
    break;
    case 'clean_all_catalogs':
        catalog_worker('clean_all_catalogs');
        show_confirmation(T_('Catalog Clean started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'clean_catalog':
        catalog_worker('clean_catalog', $catalogs);
        show_confirmation(T_('Catalog Clean started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'update_catalog_settings':
        /* No Demo Here! */
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        /* Update the catalog */
        Catalog::update_settings($_POST);

        $url       = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title     = T_('Catalog Updated');
        $body      = '';
        show_confirmation($title, $body, $url);
    break;
    case 'update_from':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        catalog_worker('update_from', null, $_POST);
        show_confirmation(T_('Subdirectory update started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'add_catalog':
        /* Wah Demo! */
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        ob_end_flush();

        if (!strlen($_POST['type']) || $_POST['type'] == 'none') {
            AmpError::add('general', T_('Error: Please select a catalog type'));
        }

        if (!strlen($_POST['name'])) {
            AmpError::add('general', T_('Error: Name not specified'));
        }

        if (!Core::form_verify('add_catalog', 'post')) {
            UI::access_denied();
            exit;
        }

        // If an error hasn't occured
        if (!AmpError::occurred()) {
            $catalog_id = Catalog::create($_POST);

            if (!$catalog_id) {
                require AmpConfig::get('prefix') . UI::find_template('show_add_catalog.inc.php');
                break;
            }

            $catalogs[] = $catalog_id;
            catalog_worker('add_to_catalog', $catalogs, $_POST);
            show_confirmation(T_('Catalog Creation started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        } else {
            require AmpConfig::get('prefix') . UI::find_template('show_add_catalog.inc.php');
        }
    break;
    case 'clear_stats':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }
        Stats::clear();
        $url      = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Catalog statistics cleared');
        $body     = '';
        show_confirmation($title, $body, $url);
    break;
    case 'show_add_catalog':
        require AmpConfig::get('prefix') . UI::find_template('show_add_catalog.inc.php');
    break;
    case 'clear_now_playing':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }
        Stream::clear_now_playing();
        show_confirmation(T_('Now Playing Cleared'), T_('All now playing data has been cleared'), AmpConfig::get('web_path') . '/admin/catalog.php');
    break;
    case 'show_disabled':
        /* Stop the demo hippies */
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $songs = Song::get_disabled();
        if (count($songs)) {
            require AmpConfig::get('prefix') . UI::find_template('show_disabled_songs.inc.php');
        } else {
            echo "<div class=\"error\" align=\"center\">" . T_('No Disabled songs found') . "</div>";
        }
    break;
    case 'show_delete_catalog':
        /* Stop the demo hippies */
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }

        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $nexturl = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
        show_confirmation(T_('Delete Catalog'), T_('Do you really want to delete this catalog?') . " -- $catalog->name ($catalog->path)", $nexturl, 1);
    break;
    case 'show_customize_catalog':
        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $catalog->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_edit_catalog.inc.php');
    break;
    case 'gather_media_art':
        catalog_worker('gather_media_art', $catalogs);
        show_confirmation(T_('Media Art Search started...'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
    break;
    case 'show_catalogs':
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_manage_catalogs.inc.php');
    break;
} // end switch

/* Show the Footer */
UI::show_footer();
