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

define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';

// Turn off output buffering we don't need it for a command line script
ob_end_clean(); 

$inDisk = AmpConfig::get('album_art_store_disk');

$sql = "SELECT `image`, `id`, `object_id`, `object_type`, `size` FROM `image`";
$db_results = Dba::read($sql);
$results = array();

while ($row = Dba::fetch_assoc($db_results)) {
    if ($inDisk) {
        $source = Art::get_from_source(array('file' => Art::get_dir_on_disk($row['object_type'], $row['object_id'], 'default') . 'art-' . $row['size'] . '.jpg'), $row['object_type']);
    } else {
        $source = $row['image'];
    }

    $id = $row['id'];
    $dimensions = Core::image_dimensions($source);
    if (!empty($dimensions)) {
        $width = (int) $dimensions['width'];
        $height = (int) $dimensions['height'];
        $sql = "Update `image` SET `width`=". $width . ",`height`=".$height." WHERE `id`='".$id."'";
        Dba::write($sql);
    }
}
