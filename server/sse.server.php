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
require_once AmpConfig::get('prefix') . '/modules/catalog/local/local.catalog.php';

if (!Access::check('interface','100')) {
    UI::access_denied();
    exit;
}
if (AmpConfig::get('demo_mode')) {
    exit;
}

ob_end_clean();
set_time_limit(0);

if (!$_REQUEST['html']) {
    define('SSE_OUTPUT', true);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
}

$worker = isset($_REQUEST['worker']) ? $_REQUEST['worker'] : null;
if (isset($_REQUEST['options'])) {
    $options = unserialize(urldecode($_REQUEST['options']));
} else {
    $options = null;
}
if (isset($_REQUEST['catalogs'])) {
    $catalogs = scrub_in(unserialize(urldecode($_REQUEST['catalogs'])));
} else {
    $catalogs = null;
}

// Free the session write lock
// Warning: Do not change any session variable after this call
session_write_close();

switch ($worker) {
    case 'catalog':
        if (defined('SSE_OUTPUT')) {
            echo "data: toggleVisible('ajax-loading')\n\n";
            ob_flush();
            flush();
        }

        Catalog::process_action($_REQUEST['action'], $catalogs, $options);

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
