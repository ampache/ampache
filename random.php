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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'get_advanced':
        $object_ids = Random::advanced($_REQUEST['type'], $_POST);

        // We need to add them to the active playlist
        if (!empty($object_ids)) {
            foreach ($object_ids as $object_id) {
                Core::get_global('user')->playlist->add_object($object_id, 'song');
            }
        }
    case 'advanced':
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_random.inc.php');
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
