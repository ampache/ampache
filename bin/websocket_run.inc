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

define('NO_SESSION', '1');
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';
ob_end_flush();

$verbose = false;
$port    = 8100;

$cargv = count($_SERVER['argv']);
if ($cargv > 1) {
    for ($x = 1; $x < $cargv; $x++) {
        if ($_SERVER['argv'][$x] == "-v") {
            $operations_string .= "\n\t- " . T_('Verbose');
            $verbose = true;
        } elseif ($_SERVER['argv'][$x] == "-p" && ($x + 1) < $cargv) {
            $port = (int) $_SERVER['argv'][++$x];
            $operations_string .= "\n\t- " . T_('Port') . ' ' . $port;
        }
    }
}

$urlinfo = parse_url(AmpConfig::get('websocket_address'));
$host    = $urlinfo['host'];
if (empty($host)) {
    $host = "localhost";
}

$app               = new Ratchet\App($host, $port, '0.0.0.0');
$brserver          = new Broadcast_Server();
$brserver->verbose = $verbose;
$app->route('/broadcast', $brserver);
$app->route('/echo', new Ratchet\Server\EchoServer, array('*'));
$app->run();

function websocket_usage()
{
    echo T_("Usage:") . "\n";
    echo T_("websocket_run.inc [-p {PORT}|-v]") . "\n\t";
    echo "\n-p {PORT}\t" . T_('Listening port, default 8100');
    echo "\n-v\t" . T_('Verbose') . " \n";
    echo "----------------------------------------------------------\n";
}
