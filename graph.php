<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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

// This file is a little weird it needs to allow API session
// this needs to be done a little better, but for now... eah
define('NO_SESSION', '1');
require_once 'lib/init.php';

// Check to see if they've got an interface session or a valid API session, if not GTFO
if (!Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')]) && !Session::exists('api', $_REQUEST['auth'])) {
    debug_event('graph', 'Access denied, checked cookie session:' . $_COOKIE[AmpConfig::get('session_name')] . ' and auth:' . $_REQUEST['auth'], 1);

    return false;
}

if (!AmpConfig::get('statistical_graphs')) {
    debug_event('graph', 'Access denied, statistical graph disabled.', 1);

    return false;
}

$type = Core::get_request('type');

$user_id     = (int) filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);
$object_type = (string) scrub_in(Core::get_request('object_type'));
if (!Core::is_library_item($object_type)) {
    $object_type = null;
}
$object_id  = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
$start_date = scrub_in($_REQUEST['start_date']);
$end_date   = scrub_in($_REQUEST['end_date']);
$zoom       = (string) scrub_in($_REQUEST['zoom']);

$width  = (int) filter_input(INPUT_GET, 'width', FILTER_SANITIZE_NUMBER_INT);
$height = (int) filter_input(INPUT_GET, 'height', FILTER_SANITIZE_NUMBER_INT);

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
