<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

require_once '../lib/init.php';
require_once AmpConfig::get('prefix') . '/modules/catalog/local.catalog.php';

if (!Access::check('interface','100')) {
    UI::access_denied();
    exit;
}

UI::show_header();

/* Big switch statement to handle various actions */
switch ($_REQUEST['action']) {
    case 'add_to_all_catalogs':
        $_REQUEST['catalogs'] = Catalog::get_catalogs();
    case 'add_to_catalog':
        toggle_visible('ajax-loading');
        ob_end_flush();
        if (AmpConfig::get('demo_mode')) { break; }
        if ($_REQUEST['catalogs']) {
            foreach ($_REQUEST['catalogs'] as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                $catalog->add_to_catalog($_POST);
            }
        }
        $url = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title = T_('Catalog Updated');
        $body = '';
        show_confirmation($title, $body, $url);
        toggle_visible('ajax-loading');
    break;
    case 'update_all_catalogs':
        $_REQUEST['catalogs'] = Catalog::get_catalogs();
    case 'update_catalog':
        toggle_visible('ajax-loading');
        ob_end_flush();
            /* If they are in demo mode stop here */
            if (AmpConfig::get('demo_mode')) { break; }

        if (isset($_REQUEST['catalogs'])) {
            foreach ($_REQUEST['catalogs'] as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                $catalog->verify_catalog();
            }
        }
        $url    = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Catalog Updated');
        $body    = '';
        show_confirmation($title,$body,$url);
        toggle_visible('ajax-loading');
    break;
    case 'full_service':
        toggle_visible('ajax-loading');
        ob_end_flush();
        /* Make sure they aren't in demo mode */
        if (AmpConfig::get('demo_mode')) { UI::access_denied(); break; }

        if (!$_REQUEST['catalogs']) {
            $_REQUEST['catalogs'] = Catalog::get_catalogs();
        }

        /* This runs the clean/verify/add in that order */
        foreach ($_REQUEST['catalogs'] as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $catalog->clean_catalog();
            $catalog->verify_catalog();
            $catalog->add_to_catalog();
        }
        Dba::optimize_tables();
        $url    = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Catalog Updated');
        $body    = '';
        show_confirmation($title,$body,$url);
        toggle_visible('ajax-loading');
    break;
    case 'delete_catalog':
        /* Make sure they aren't in demo mode */
        if (AmpConfig::get('demo_mode')) { break; }

        if (!Core::form_verify('delete_catalog')) {
            UI::access_denied();
            exit;
        }

        /* Delete the sucker, we don't need to check perms as thats done above */
        Catalog::delete($_GET['catalog_id']);
        $next_url = AmpConfig::get('web_path') . '/admin/catalog.php';
        show_confirmation(T_('Catalog Deleted'), T_('The Catalog and all associated records have been deleted'),$next_url);
    break;
    case 'show_delete_catalog':
        $catalog_id = scrub_in($_GET['catalog_id']);

        $next_url = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&catalog_id=' . scrub_out($catalog_id);
        show_confirmation(T_('Catalog Delete'), T_('Confirm Deletion Request'),$next_url,1,'delete_catalog');
    break;
    case 'enable_disabled':
        if (AmpConfig::get('demo_mode')) { break; }

        $songs = $_REQUEST['song'];

        if (count($songs)) {
            foreach ($songs as $song_id) {
                Song::update_enabled(true, $song_id);
            }
            $body = count($songs) . ngettext(' Song Enabled', ' Songs Enabled', count($songs));
        } else {
            $body = T_('No Disabled Songs selected');
        }
        $url    = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = count($songs) . ngettext(' Disabled Song Processed', ' Disabled Songs Processed', count($songs));
        show_confirmation($title,$body,$url);
    break;
    case 'clean_all_catalogs':
        $_REQUEST['catalogs'] = Catalog::get_catalogs();
    case 'clean_catalog':
        toggle_visible('ajax-loading');
        ob_end_flush();
            /* If they are in demo mode stop them here */
            if (AmpConfig::get('demo_mode')) { break; }

        // Make sure they checked something
        if (isset($_REQUEST['catalogs'])) {
            foreach ($_REQUEST['catalogs'] as $catalog_id) {
                $catalog = Catalog::create_from_id($catalog_id);
                $catalog->clean_catalog();
            } // end foreach catalogs
            Dba::optimize_tables();
        }

        $url     = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Catalog Cleaned');
        $body    = '';
        show_confirmation($title,$body,$url);
        toggle_visible('ajax-loading');
    break;
    case 'update_catalog_settings':
        /* No Demo Here! */
        if (AmpConfig::get('demo_mode')) { break; }

        /* Update the catalog */
        Catalog::update_settings($_POST);

        $url     = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title     = T_('Catalog Updated');
        $body    = '';
        show_confirmation($title,$body,$url);
    break;
    case 'update_from':
        if (AmpConfig::get('demo_mode')) { break; }

        // First see if we need to do an add
        if ($_POST['add_path'] != '/' AND strlen($_POST['add_path'])) {
            if ($catalog_id = Catalog_local::get_from_path($_POST['add_path'])) {
                $catalog = Catalog::create_from_id($catalog_id);
                $catalog->add_to_catalog(array('subdirectory'=>$_POST['add_path']));
            }
        } // end if add

        // Now check for an update
        if ($_POST['update_path'] != '/' AND strlen($_POST['update_path'])) {
            if ($catalog_id = Catalog_local::get_from_path($_POST['update_path'])) {
                $songs = Song::get_from_path($_POST['update_path']);
                foreach ($songs as $song_id) { Catalog::update_single_item('song',$song_id); }
            }
        } // end if update

        echo T_("Done.");
    break;
    case 'add_catalog':
        /* Wah Demo! */
        if (AmpConfig::get('demo_mode')) { break; }

        ob_end_flush();

        if (!strlen($_POST['type']) || $_POST['type'] == 'none') {
            Error::add('general', T_('Error: Please select a catalog type'));
        }

        if (!strlen($_POST['name'])) {
            Error::add('general', T_('Error: Name not specified'));
        }

        if (!Core::form_verify('add_catalog','post')) {
            UI::access_denied();
            exit;
        }

        // If an error hasn't occured
        if (!Error::occurred()) {

            $catalog_id = Catalog::create($_POST);

            if (!$catalog_id) {
                require AmpConfig::get('prefix') . '/templates/show_add_catalog.inc.php';
                break;
            }

            $catalog = Catalog::create_from_id($catalog_id);

            // Run our initial add
            $catalog->add_to_catalog($_POST);

            UI::show_box_top(T_('Catalog Created'), 'box box_catalog_created');
            echo "<h2>" .  T_('Catalog Created') . "</h2>";
            Error::display('general');
            Error::display('catalog_add');
            UI::show_box_bottom();

            show_confirmation('','', AmpConfig::get('web_path').'/admin/catalog.php');

        } else {
            require AmpConfig::get('prefix') . '/templates/show_add_catalog.inc.php';
        }
    break;
    case 'clear_stats':
        if (AmpConfig::get('demo_mode')) { UI::access_denied(); break; }
        Stats::clear();
        $url    = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title    = T_('Catalog statistics cleared');
        $body    = '';
        show_confirmation($title, $body, $url);
    break;
    case 'show_add_catalog':
        require AmpConfig::get('prefix') . '/templates/show_add_catalog.inc.php';
    break;
    case 'clear_now_playing':
        if (AmpConfig::get('demo_mode')) { UI::access_denied(); break; }
        Stream::clear_now_playing();
        show_confirmation(T_('Now Playing Cleared'), T_('All now playing data has been cleared'),AmpConfig::get('web_path') . '/admin/catalog.php');
    break;
    case 'show_disabled':
        /* Stop the demo hippies */
        if (AmpConfig::get('demo_mode')) { break; }

        $songs = Song::get_disabled();
        if (count($songs)) {
            require AmpConfig::get('prefix') . '/templates/show_disabled_songs.inc.php';
        } else {
            echo "<div class=\"error\" align=\"center\">" . T_('No Disabled songs found') . "</div>";
        }
    break;
    case 'show_delete_catalog':
        /* Stop the demo hippies */
        if (AmpConfig::get('demo_mode')) { UI::access_denied(); break; }

        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $nexturl = AmpConfig::get('web_path') . '/admin/catalog.php?action=delete_catalog&amp;catalog_id=' . scrub_out($_REQUEST['catalog_id']);
        show_confirmation(T_('Delete Catalog'), T_('Do you really want to delete this catalog?') . " -- $catalog->name ($catalog->path)",$nexturl,1);
    break;
    case 'show_customize_catalog':
        $catalog = Catalog::create_from_id($_REQUEST['catalog_id']);
        $catalog->format();
        require_once AmpConfig::get('prefix') . '/templates/show_edit_catalog.inc.php';
    break;
    case 'gather_media_art':
        toggle_visible('ajax-loading');
        ob_end_flush();

        $catalogs = $_REQUEST['catalogs'] ? $_REQUEST['catalogs'] : Catalog::get_catalogs();

        // Iterate throught the catalogs and gather as needed
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            require AmpConfig::get('prefix') . '/templates/show_gather_art.inc.php';
            flush();
            $catalog->gather_art();
        }
        $url     = AmpConfig::get('web_path') . '/admin/catalog.php';
        $title     = T_('Media Art Search Finished');
        $body    = '';
        show_confirmation($title,$body,$url);
    break;
    case 'show_catalogs':
    default:
        require_once AmpConfig::get('prefix') . '/templates/show_manage_catalogs.inc.php';
    break;
} // end switch

/* Show the Footer */
UI::show_footer();
