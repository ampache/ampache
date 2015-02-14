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

// This file is a little weird it needs to allow API session
// this needs to be done a little better, but for now... eah
define('NO_SESSION','1');
require_once 'lib/init.php';

// Check to see if they've got an interface session or a valid API session, if not GTFO
if (!Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')]) && !Session::exists('api', $_REQUEST['auth'])) {
    debug_event('graph', 'Access denied, checked cookie session:' . $_COOKIE[AmpConfig::get('session_name')] . ' and auth:' . $_REQUEST['auth'], 1);
    exit;
}

if (!AmpConfig::get('statistical_graphs')) {
    debug_event('graph', 'Access denied, statistical graph disabled.', 1);
    exit;
}

$type = $_REQUEST['type'];

$user_id = intval($_REQUEST['user_id']);
$object_type = (string) scrub_in($_REQUEST['object_type']);
if (!Core::is_library_item($object_type)) {
    $object_type = null;
}
$object_id = intval($_REQUEST['object_id']);
$start_date = scrub_in($_REQUEST['start_date']);
$end_date = scrub_in($_REQUEST['end_date']);
$zoom = (string) scrub_in($_REQUEST['zoom']);

$width = intval($_REQUEST['width']);
$height = intval($_REQUEST['height']);

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
