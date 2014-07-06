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

require_once 'lib/init.php';

require_once AmpConfig::get('prefix') . '/templates/header.inc.php';

$object_type = $_GET['object_type'];
$object_id = $_GET['object_id'];
$burl = '';
if (isset($_GET['burl'])) {
    $burl = rawurldecode($_GET['burl']);
}

/* Switch on Action */
switch ($_REQUEST['action']) {
    case 'clear_art':
        if (!$GLOBALS['user']->has_access('75')) { UI::access_denied(); }
        $art = new Art($object_id, $object_type);
        $art->reset();
        show_confirmation(T_('Art Cleared'), T_('Art information has been removed from the database'), $burl);
    break;
    // Upload art
    case 'upload_art':
        // we didn't find anything
        if (empty($_FILES['file']['tmp_name'])) {
            show_confirmation(T_('Art Not Located'), T_('Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'), $burl);
            break;
        }

        // Pull the image information
        $data = array('file'=>$_FILES['file']['tmp_name']);
        $image_data = Art::get_from_source($data, $object_type);

        // If we got something back insert it
        if ($image_data) {
            $art = new Art($object_id, $object_type);
            $art->insert($image_data,$_FILES['file']['type']);
            show_confirmation(T_('Art Inserted'), '', $burl);
        }
        // Else it failed
        else {
            show_confirmation(T_('Art Not Located'), T_('Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'), $burl);
        }

    break;
    case 'find_art':
        // If not a user then kick em out
        if (!Access::check('interface','25')) { UI::access_denied(); exit; }

        // Prevent the script from timing out
        set_time_limit(0);

        $item = new $object_type($object_id);
        $item->format();
        $art = new Art($object_id, $object_type);
        $images = array();
        $cover_url = array();

        // If we've got an upload ignore the rest and just insert it
        if (!empty($_FILES['file']['tmp_name'])) {
            $path_info = pathinfo($_FILES['file']['name']);
            $upload['file'] = $_FILES['file']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data = Art::get_from_source($upload, $object_type);

            if ($image_data) {
                $art->insert($image_data,$upload['0']['mime']);
                show_confirmation(T_('Art Inserted'), '', $burl);
                break;

            } // if image data

        } // if it's an upload

        $keywords = $item->get_keywords();
        $keyword = '';
        $options = array();
        foreach ($keywords as $key => $word) {
            if (isset($_REQUEST['option_' . $key])) {
                $word['value'] = $_REQUEST['option_' . $key];
            }
            $options[$key] = $word['value'];
            if ($word['important']) {
                if (!empty($word['value'])) {
                    $keyword .= ' ' . $word['value'];
                }
            }
        }
        $options['keyword'] = trim($keyword);

        // Attempt to find the art.
        $images = $art->gather($options);

        if (!empty($_REQUEST['cover'])) {
            $path_info = pathinfo($_REQUEST['cover']);
            $cover_url[0]['url']     = scrub_in($_REQUEST['cover']);
            $cover_url[0]['mime']     = 'image/' . $path_info['extension'];
        }
        $images = array_merge($cover_url, $images);

        // If we've found anything then go for it!
        if (count($images)) {
            // We don't want to store raw's in here so we need to strip them out into a separate array
            foreach ($images as $index=>$image) {
                if ($image['raw']) {
                    unset($images[$index]['raw']);
                }
            } // end foreach
            // Store the results for further use
            $_SESSION['form']['images'] = $images;
            require_once AmpConfig::get('prefix') . '/templates/show_arts.inc.php';
        }
        // Else nothing
        else {
            show_confirmation(T_('Art Not Located'), T_('Art could not be located at this time. This may be due to write access error, or the file is not received correctly.'), $burl);
        }

        require_once AmpConfig::get('prefix') . '/templates/show_get_art.inc.php';

    break;
    case 'select_art':

        /* Check to see if we have the image url still */
        $image_id = $_REQUEST['image'];

        // Prevent the script from timing out
        set_time_limit(0);

        $image = Art::get_from_source($_SESSION['form']['images'][$image_id], 'album');
        $mime = $_SESSION['form']['images'][$image_id]['mime'];

        // Special case for albums, I'm not sure if we should keep it, remove it or find a generic way
        if ($object_type == 'album') {
            $album = new $object_type($object_id);
            $album_groups = $album->get_group_disks_ids();
            foreach ($album_groups as $a_id) {
                $art = new Art($a_id, $object_type);
                $art->insert($image, $mime);
            }
        } else {
            $art = new Art($object_id, $object_type);
            $art->insert($image, $mime);
        }

        header("Location:" . $burl);
    break;
}

UI::show_footer();
