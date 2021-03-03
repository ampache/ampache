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

$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';
require_once AmpConfig::get('prefix') . '/modules/catalog/local/local.catalog.php';

if (!Access::check('interface', 75)) {
    UI::access_denied();

    return false;
}

UI::show_header();

$catalogs = isset($_REQUEST['catalogs']) ? filter_var_array($_REQUEST['catalogs'], FILTER_SANITIZE_STRING) : array();
$action   = Core::get_request('action');
// If only one catalog, check it is ready.
if (is_array($catalogs) && count($catalogs) == 1 && $action !== 'delete_catalog' && $action !== 'show_delete_catalog') {
    // If not ready, display the data to make it ready / stop the action.
    $catalog = Catalog::create_from_id($catalogs[0]);
    if (!$catalog->isReady()) {
        if (!isset($_REQUEST['perform_ready'])) {
            $catalog->show_ready_process();
            UI::show_footer();

            return false;
        } else {
            $catalog->perform_ready();
        }
    }
}

// Big switch statement to handle various actions
switch ($_REQUEST['action']) {
    case 'add_to_all_catalogs':
        catalog_worker('add_to_all_catalogs');
        show_confirmation(T_('Catalog update process has started'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'add_to_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }
        catalog_worker('add_to_catalog', $catalogs);
        show_confirmation(T_('Catalog update process has started'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'update_all_catalogs':
        catalog_worker('update_all_catalogs');
        show_confirmation(T_('Catalog update process has started'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'update_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        catalog_worker('update_catalog', $catalogs);
        show_confirmation(T_('Catalog update process has started'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'full_service':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }

        catalog_worker('full_service', $catalogs);
        show_confirmation(T_('Catalog update process has started'), '', AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'delete_catalog':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        if (!Core::form_verify('delete_catalog')) {
            UI::access_denied();

            return false;
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
            show_confirmation(T_('No Problem'), T_('The Catalog has been deleted'), $next_url);
        } else {
            show_confirmation(T_("There Was a Problem"), T_("There was an error deleting this Catalog"), $next_url);
        }
        break;
    case 'show_delete_catalog':
        $next_url = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&catalogs[]=' . implode(',', $catalogs);
        show_confirmation(T_('Are You Sure?'), T_('This will permanently delete your Catalog'), $next_url, 1, 'delete_catalog');
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
            $body = count($songs) . ' ' . nT_('Song has been enabled', 'Songs have been enabled', count($songs));
        } else {
            $body = T_("You didn't select any disabled Songs");
        }
        $url      = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Finished Processing Disabled Songs');
        show_confirmation($title, $body, $url);
        break;
    case 'clean_all_catalogs':
        catalog_worker('clean_all_catalogs');
        show_confirmation(T_('No Problem'), T_('The Catalog cleaning process has started'), AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'clean_catalog':
        catalog_worker('clean_catalog', $catalogs);
        show_confirmation(T_('No Problem'), T_('The Catalog cleaning process has started'), AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'update_catalog_settings':
        /* No Demo Here! */
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        /* Update the catalog */
        Catalog::update_settings($_POST);

        $url       = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title     = T_('No Problem');
        $body      = T_('The Catalog has been updated');
        show_confirmation($title, $body, $url);
        break;
    case 'update_from':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        catalog_worker('update_from', null, $_POST);
        show_confirmation(T_('No Problem'), T_('The subdirectory update has started'), AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'add_catalog':
        /* Wah Demo! */
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        ob_end_flush();

        if (!strlen(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) || filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) == 'none') {
            AmpError::add('general', T_('Please select a Catalog type'));
        }

        if (!strlen(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))) {
            AmpError::add('general', T_('Please enter a Catalog name'));
        }

        if (!Core::form_verify('add_catalog', 'post')) {
            UI::access_denied();

            return false;
        }

        // If an error hasn't occurred
        if (!AmpError::occurred()) {
            $catalog_id = Catalog::create($_POST);

            if (!$catalog_id) {
                require AmpConfig::get('prefix') . UI::find_template('show_add_catalog.inc.php');
                break;
            }

            $catalogs[] = $catalog_id;
            catalog_worker('add_to_catalog', $catalogs, $_POST);
            show_confirmation(T_('No Problem'), T_('The Catalog creation process has started'), AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
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
        $title    = T_('No Problem');
        $body     = T_('Catalog statistics have been cleared');
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
        show_confirmation(T_('No Problem'), T_('All Now Playing data has been cleared'), AmpConfig::get('web_path') . '/admin/catalog.php');
        break;
    case 'show_disabled':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $songs = Song::get_disabled();
        if (count($songs)) {
            $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
            require AmpConfig::get('prefix') . UI::find_template('show_disabled_songs.inc.php');
        } else {
            echo '<div class="error show-disabled">' . T_('No disabled Songs found') . '</div>';
        }
        break;
    case 'show_delete_catalog':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            break;
        }

        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $nexturl = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
        show_confirmation(T_('Are You Sure?'),
                /* HINT: Catalog Name */
                sprintf(T_('This will permanently delete the catalog "%s"'), $catalog->name), $nexturl, 1);
        break;
    case 'show_customize_catalog':
        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $catalog->format();
        require_once AmpConfig::get('prefix') . UI::find_template('show_edit_catalog.inc.php');
        break;
    case 'gather_media_art':
        catalog_worker('gather_media_art', $catalogs);
        show_confirmation(T_('No Problem'), T_('The Catalog art search has started'), AmpConfig::get('web_path') . '/admin/catalog.php', 0, 'confirmation', false);
        break;
    case 'show_catalogs':
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_manage_catalogs.inc.php');
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
