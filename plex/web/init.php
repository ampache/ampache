<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

require_once('../../lib/class/plex_xml_data.class.php');

$ow_config = array(
    'http_host' => $_SERVER["SERVER_NAME"] . ':' . $_SERVER["SERVER_PORT"],
    'web_path' => '/web'
 );

require_once '../../lib/init.php';

if (!AmpConfig::get('plex_backend')) {
    echo "Disabled.";
    exit;
}

if (!defined('NO_SESSION') && !Access::check('interface', '100')) {
    echo T_('Unauthorized.');
    exit();
}
