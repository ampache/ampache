<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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
if (AmpConfig::get('demo_mode')) { exit; }

ob_end_clean();
set_time_limit(0);

if (!$_REQUEST['html']) {
    define('SSE_OUTPUT', true);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
}

$worker = isset($_REQUEST['worker']) ? $_REQUEST['worker'] : null;
$options = unserialize(urldecode(scrub_in($_REQUEST['options'])));

$_REQUEST['catalogs'] = scrub_in(unserialize(urldecode($_REQUEST['catalogs'])));

switch ($worker) {
    case 'catalog':
        if (defined('SSE_OUTPUT')) {
            echo "data: toggleVisible('ajax-loading')\n\n";
            ob_flush();
            flush();
        }

        switch ($_REQUEST['action']) {
            case 'add_to_all_catalogs':
                $_REQUEST['catalogs'] = Catalog::get_catalogs();
            case 'add_to_catalog':
                if ($_REQUEST['catalogs']) {
                    foreach ($_REQUEST['catalogs'] as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog($_POST);
                        }
                    }
                }
                break;
            case 'update_all_catalogs':
                $_REQUEST['catalogs'] = Catalog::get_catalogs();
            case 'update_catalog':
                if (isset($_REQUEST['catalogs'])) {
                    foreach ($_REQUEST['catalogs'] as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->verify_catalog();
                        }
                    }
                }
                break;
            case 'full_service':
                if (!$_REQUEST['catalogs']) {
                    $_REQUEST['catalogs'] = Catalog::get_catalogs();
                }

                /* This runs the clean/verify/add in that order */
                foreach ($_REQUEST['catalogs'] as $catalog_id) {
                    $catalog = Catalog::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        $catalog->clean_catalog();
                        $catalog->verify_catalog();
                        $catalog->add_to_catalog();
                    }
                }
                Dba::optimize_tables();
                break;
            case 'clean_all_catalogs':
                $_REQUEST['catalogs'] = Catalog::get_catalogs();
            case 'clean_catalog':
                // Make sure they checked something
                if (isset($_REQUEST['catalogs'])) {
                    foreach ($_REQUEST['catalogs'] as $catalog_id) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->clean_catalog();
                        }
                    } // end foreach catalogs
                    Dba::optimize_tables();
                }
                break;
            case 'update_from':
                $catalog_id = 0;
                // First see if we need to do an add
                if ($_REQUEST['add_path'] != '/' AND strlen($_REQUEST['add_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($_REQUEST['add_path'])) {
                        $catalog = Catalog::create_from_id($catalog_id);
                        if ($catalog !== null) {
                            $catalog->add_to_catalog(array('subdirectory'=>$_REQUEST['add_path']));
                        }
                    }
                } // end if add

                // Now check for an update
                if ($_REQUEST['update_path'] != '/' AND strlen($_REQUEST['update_path'])) {
                    if ($catalog_id = Catalog_local::get_from_path($_REQUEST['update_path'])) {
                        $songs = Song::get_from_path($_REQUEST['update_path']);
                        foreach ($songs as $song_id) { Catalog::update_single_item('song',$song_id); }
                    }
                } // end if update

                if ($catalog_id <= 0) {
                    Error::add('general', T_("This subdirectory is not part of an existing catalog. Update cannot be processed."));
                }
                break;
            case 'add_catalog':
                $catalog_id = intval($_REQUEST['catalog_id']);
                $catalog = Catalog::create_from_id($catalog_id);
                if ($catalog !== null) {
                    // Run our initial add
                    $catalog->add_to_catalog($options);

                    if (!defined('SSE_OUTPUT')) {
                        Error::display('catalog_add');
                    }
                }
                break;
            case 'gather_media_art':
                $catalogs = $_REQUEST['catalogs'] ? $_REQUEST['catalogs'] : Catalog::get_catalogs();

                // Iterate throught the catalogs and gather as needed
                foreach ($catalogs as $catalog_id) {
                    $catalog = Catalog::create_from_id($catalog_id);
                    if ($catalog !== null) {
                        require AmpConfig::get('prefix') . '/templates/show_gather_art.inc.php';
                        flush();
                        $catalog->gather_art();
                    }
                }
                break;
        }

        if (defined('SSE_OUTPUT')) {
            echo "data: toggleVisible('ajax-loading')\n\n";
            ob_flush();
            flush();

            echo "data: stop_sse_worker()\n\n";
            ob_flush();
            flush();
        } else {
            Error::display('general');
        }

        break;
}
