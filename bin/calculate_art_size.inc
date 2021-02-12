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

$inDisk   = AmpConfig::get('album_art_store_disk');
$localDir = AmpConfig::get('local_metadata_dir');

$sql        = "SELECT `image`, `id`, `object_id`, `object_type`, `size` FROM `image`";
$db_results = Dba::read($sql);
$results    = array();

while ($row = Dba::fetch_assoc($db_results)) {
    $folder = Art::get_dir_on_disk($row['object_type'], $row['object_id'], 'default');
    if ($inDisk && $localDir && $folder) {
        $source = Art::get_from_source(array('file' => $folder . 'art-' . $row['size'] . '.jpg'), $row['object_type']);
    } else {
        $source = $row['image'];
    }

    $art_id     = $row['id'];
    $dimensions = Core::image_dimensions($source);
    if (!empty($dimensions) && ((int) $dimensions['width'] > 0 && (int) $dimensions['height'] > 0)) {
        $width  = (int) $dimensions['width'];
        $height = (int) $dimensions['height'];
        $sql    = "UPDATE `image` SET `width`=" . $width . ", `height`=" . $height . " WHERE `id`='" . $art_id . "'";
        Dba::write($sql);
    }
}
