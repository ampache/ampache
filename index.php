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

$action = Core::get_request('action');

if (!Core::is_session_started()) {
    session_start();
}
$_SESSION['catalog'] = 0;

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
if (AmpConfig::get('refresh_limit') > 5 && AmpConfig::get('home_now_playing')) {
    $refresh_limit = AmpConfig::get('refresh_limit');
    $ajax_url      = '?page=index&action=reloadnp';
    require_once AmpConfig::get('prefix') . UI::find_template('javascript_refresh.inc.php');
}

require_once AmpConfig::get('prefix') . UI::find_template('show_index.inc.php');

// Show the Footer
UI::show_query_stats();
UI::show_footer();
