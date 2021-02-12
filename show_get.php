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

if (isset($_REQUEST['param_name'])) {
    $name = (string) scrub_in(filter_var($_REQUEST['param_name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
    if (isset($_REQUEST[$name])) {
        echo $name . ": " . (string) scrub_in(filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
    }
}

if (isset($_REQUEST['error'])) {
    $error             = (string) scrub_in(filter_var($_REQUEST['error'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
    $error_description = (string) scrub_in(filter_var($_REQUEST['error_description'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
    echo $error . " error: " . $error_description;
}
