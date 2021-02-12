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
define('TABLE_RENDERED', 1);

// Temporary workaround to avoid sorting on custom base requests
define('NO_BROWSE_SORTING', true);

$time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';

// Switch on the actions
switch ($_REQUEST['action']) {
    // Show a Users "Profile" page
    case 'show_user':
        $client = new User((int) Core::get_request('user_id'));
        require_once AmpConfig::get('prefix') . UI::find_template('show_user.inc.php');
    break;
    // Show stats
    case 'newest':
        require_once AmpConfig::get('prefix') . UI::find_template('show_newest.inc.php');
        break;
    case 'popular':
        require_once AmpConfig::get('prefix') . UI::find_template('show_popular.inc.php');
        break;
    case 'highest':
        require_once AmpConfig::get('prefix') . UI::find_template('show_highest.inc.php');
        break;
    case 'userflag':
        require_once AmpConfig::get('prefix') . UI::find_template('show_userflag.inc.php');
        break;
    case 'recent':
        $user_id = Core::get_request('user_id');
        require_once AmpConfig::get('prefix') . UI::find_template('show_recent.inc.php');
        break;
    case 'wanted':
        require_once AmpConfig::get('prefix') . UI::find_template('show_wanted.inc.php');
        break;
    case 'share':
        require_once AmpConfig::get('prefix') . UI::find_template('show_shares.inc.php');
        break;
    case 'upload':
        require_once AmpConfig::get('prefix') . UI::find_template('show_uploads.inc.php');
        break;
    case 'graph':
        Graph::display_from_request();
        break;
    case 'show':
    default:
        if (Access::check('interface', 50)) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_stats.inc.php');
        }
        break;
} // end switch on action

show_table_render(false, true);

// Show the Footer
UI::show_query_stats();
UI::show_footer();
