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

require_once 'lib/init.php';

UI::show_header();

$action = isset($_REQUEST['action']) ? scrub_in($_REQUEST['action']) : null;

if (!Core::is_session_started()) {
    session_start();
}
$_SESSION['catalog'] = 0;

/**
 * Check for the refresh mojo, if it's there then require the
 * refresh_javascript include. Must be greater then 5, I'm not
 * going to let them break their servers
 */
if (AmpConfig::get('refresh_limit') > 5) {
    $refresh_limit = AmpConfig::get('refresh_limit');
    $ajax_url = '?page=index&action=reloadnp';
    require_once AmpConfig::get('prefix') . '/templates/javascript_refresh.inc.php';
}

require_once AmpConfig::get('prefix') . '/templates/show_index.inc.php';

UI::show_footer();
