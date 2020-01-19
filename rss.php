<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION', '1');
require_once 'lib/init.php';

/* Check Perms */
if (!AmpConfig::get('use_rss') || AmpConfig::get('demo_mode')) {
    UI::access_denied();

    return false;
}

// Add in our base hearder defining the content type
header("Content-Type: application/xml; charset=" . AmpConfig::get('site_charset'));

$type   = Core::get_request('type');
$rss    = new Ampache_RSS($type);
$params = null;
if ($type === "podcast") {
    $params                = array();
    $params['object_type'] = Core::get_request('object_type');
    $params['object_id']   = filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
}
echo $rss->get_xml($params);
