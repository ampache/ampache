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
$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

// Turn off output buffering we don't need it for a command line script
ob_end_clean();

echo "This file cleans the image table for items that don't fit into set dimensions\n";
$run = true;
if (((!AmpConfig::get('album_art_min_width')) && (!AmpConfig::get('album_art_max_width'))) ||
    ((!AmpConfig::get('album_art_min_height')) && (!AmpConfig::get('album_art_max_height')))) {
    echo "\nERROR:\n    A minimum OR maximum height/width must be specified in the config\n\n";
    echo "Minimum Dimensions: album_art_min_width AND album_art_min_height\n";
    echo "Maximum Dimensions: album_art_max_width AND album_art_max_height\n\n";
    $run = false;
}

if ($run) {
    if (Art::clean_art_by_dimension()) {
        echo "Clean Completed\n";
    } else {
        echo "Clean Failed. Check your logs.\n";
    }
}
