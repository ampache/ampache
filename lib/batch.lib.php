<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

/**
 * get_media_files
 *
 * Takes an array of media ids and returns an array of the actual filenames
 *
 * @param    array    $media_ids    Media IDs.
 */
function get_media_files($media_ids)
{
    $media_files = array();

    $total_size = 0;
    foreach ($media_ids as $element) {
        if (is_array($element)) {
            if (isset($element['object_type'])) {
                $type = $element['object_type'];
                $id = $element['object_id'];
            } else {
                $type = array_shift($element);
                $id = array_shift($element);
            }
            $media = new $type($id);
        } else {
            $media = new Song($element);
        }
        if ($media->enabled) {
            $media->format();
            $total_size += sprintf("%.2f",($media->size/1048576));
            $dirname = '';
            $parent = $media->get_parent();
            if ($parent != null) {
                $pobj = new $parent['object_type']($parent['object_id']);
                $pobj->format();
                $dirname = $pobj->get_fullname();
            }
            if (!array_key_exists($dirname, $media_files)) {
                $media_files[$dirname] = array();
            }
            array_push($media_files[$dirname], Core::conv_lc_file($media->file));
        }
    }

    return array($media_files, $total_size);
} //get_media_files

/**
 * send_zip
 *
 * takes array of full paths to medias
 * zips them and sends them
 *
 * @param    string    $name    name of the zip file to be created
 * @param    array    $media_files    array of full paths to medias to zip create w/ call to get_media_files
 */
function send_zip($name, $media_files)
{
    /* Require needed library */
    require_once AmpConfig::get('prefix') . '/modules/zipstream/zipstream.php';
    $arc = new ZipStream($name . ".zip" );
    $options = array(
        'comment'       => AmpConfig::get('file_zip_comment'),
    );

    foreach ($media_files as $dir => $files) {
        foreach ($files as $file) {
            $arc->add_file_from_path($dir . "/" . basename($file), $file, $options);
        }
    }

    $arc->finish();
} // send_zip

/**
 * check_can_zip
 *
 * Check that an object type is allowed to be zipped.
 *
 * @param string $object_type
 */
function check_can_zip($object_type)
{
    $allowed = true;
    if (AmpConfig::get('allow_zip_types')) {
        $allowed = false;
        $allowed_types = explode(',', AmpConfig::get('allow_zip_types'));
        foreach ($allowed_types as $atype) {
            if (trim($atype) == $object_type) {
                $allowed = true;
                break;
            }
        }
    }

    return $allowed;
}
