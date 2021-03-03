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

// This file is a little weird it needs to allow API session
// this needs to be done a little better, but for now... eah
define('NO_SESSION', '1');
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

// Check to see if they've got an interface session or a valid API session, if not GTFO
if (!Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')]) && !Session::exists('api', $_REQUEST['auth'])) {
    debug_event('graph', 'Access denied, checked cookie session:' . $_COOKIE[AmpConfig::get('session_name')], 1);
    exit;
}

if (!AmpConfig::get('statistical_graphs') || !is_dir(AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/')) {
    debug_event('graph', 'Access denied, statistical graph disabled.', 1);
    exit;
}

$type = $_REQUEST['type'];

$user_id     = (int) ($_REQUEST['user_id']);
$object_type = (string) scrub_in($_REQUEST['object_type']);
if (!Core::is_library_item($object_type)) {
    $object_type = null;
}
$object_id  = (int) ($_REQUEST['object_id']);
$start_date = (int) scrub_in($_REQUEST['start_date']);
$end_date   = (int) scrub_in($_REQUEST['end_date']);
$zoom       = (string) scrub_in($_REQUEST['zoom']);

$width  = (int) ($_REQUEST['width']);
$height = (int) ($_REQUEST['height']);

$graph = new Graph();

switch ($type) {
    case 'user_hits':
        $graph->render_user_hits($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
        break;
    case 'user_bandwidth':
        $graph->render_user_bandwidth($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
        break;
    case 'catalog_files':
        $graph->render_catalog_files($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
        break;
    case 'catalog_size':
        $graph->render_catalog_size($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
        break;
}
