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

require_once AmpConfig::get('prefix') . UI::find_template('header.inc.php');

$object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
$object_id   = (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT);
if (!Core::is_library_item($object_type)) {
    UI::access_denied();

    return false;
}
$burl = '';
if (filter_has_var(INPUT_GET, 'burl')) {
    $burl = base64_decode(Core::get_get('burl'));
}

$item = new $object_type($object_id);
$item->format();
// If not a content manager user then kick em out
if (!Access::check('interface', 50) && (!Access::check('interface', 25) || $item->get_user_owner() != Core::get_global('user')->id)) {
    UI::access_denied();

    return false;
}
$keywords = $item->get_keywords();
$keyword  = '';
$options  = array();
foreach ($keywords as $key => $word) {
    if (isset($_REQUEST['option_' . $key])) {
        $word['value'] = $_REQUEST['option_' . $key];
    }
    $options[$key] = $word['value'];
    if ($word['important'] && !empty($word['value'])) {
        $keyword .= ' ' . $word['value'];
    }
}
$options['keyword'] = trim($keyword);

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'clear_art':
        $art = new Art($object_id, $object_type);
        $art->reset();
        show_confirmation(T_('No Problem'), T_('Art information has been removed from the database'), $burl);
        break;
    // Upload art
    case 'upload_art':
        // we didn't find anything
        if (empty($_FILES['file']['tmp_name'])) {
            show_confirmation(T_("There Was a Problem"), T_('Art could not be located at this time. This may be due to write access error, or the file was not received correctly'), $burl);
            break;
        }

        // Pull the image information
        $data       = array('file' => $_FILES['file']['tmp_name']);
        $image_data = Art::get_from_source($data, $object_type);

        // If we got something back insert it
        if ($image_data !== '') {
            $art = new Art($object_id, $object_type);
            if ($art->insert($image_data, $_FILES['file']['type'])) {
                show_confirmation(T_('No Problem'), T_('Art has been added'), $burl);
            } else {
                show_confirmation(T_("There Was a Problem"), T_('Art file failed to insert, check the dimensions are correct.'), $burl);
            }
        }
        // Else it failed
        else {
            show_confirmation(T_("There Was a Problem"), T_('Art could not be located at this time. This may be due to write access error, or the file was not received correctly'), $burl);
        }
        break;
    case 'show_art_dlg':
        require_once AmpConfig::get('prefix') . UI::find_template('show_get_art.inc.php');
        break;
    case 'find_art':
        // Prevent the script from timing out
        set_time_limit(0);

        $art       = new Art($object_id, $object_type);
        $images    = array();
        $cover_url = array();
        $limit     = 0;
        if (isset($_REQUEST['artist_filter'])) {
            $options['artist_filter'] =true;
        }
        if (isset($_REQUEST['search_limit'])) {
            $options['search_limit'] = $limit = (int) $_REQUEST['search_limit'];
        }
        if (isset($_REQUEST['year_filter']) && !empty($_REQUEST['year_filter'])) {
            $options['year_filter'] = 'year:' . $_REQUEST['year_filter'];
        }
        // If we've got an upload ignore the rest and just insert it
        if (!empty($_FILES['file']['tmp_name'])) {
            $path_info      = pathinfo($_FILES['file']['name']);
            $upload['file'] = $_FILES['file']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data     = Art::get_from_source($upload, $object_type);

            if ($image_data != '') {
                if ($art->insert($image_data, $upload['0']['mime'])) {
                    show_confirmation(T_('No Problem'), T_('Art has been added'), $burl);
                } else {
                    show_confirmation(T_("There Was a Problem"), T_('Art file failed to insert, check the dimensions are correct.'), $burl);
                }
                break;
            } // if image data
        } // if it's an upload

        // Attempt to find the art.
        $images = $art->gather($options, $limit);

        if (!empty($_REQUEST['cover'])) {
            $path_info            = pathinfo($_REQUEST['cover']);
            $cover_url[0]['url']  = scrub_in($_REQUEST['cover']);
            $cover_url[0]['mime'] = 'image/' . $path_info['extension'];
        }
        $images = array_merge($cover_url, $images);

        // If we've found anything then go for it!
        if (count($images)) {
            // We don't want to store raw's in here so we need to strip them out into a separate array
            foreach ($images as $index => $image) {
                if ($image['raw']) {
                    unset($images[$index]['raw']);
                }
            } // end foreach
            // Store the results for further use
            $_SESSION['form']['images'] = $images;
            require_once AmpConfig::get('prefix') . UI::find_template('show_arts.inc.php');
        }
        require_once AmpConfig::get('prefix') . UI::find_template('show_get_art.inc.php');
        break;
    case 'select_art':
        /* Check to see if we have the image url still */
        $image_id = $_REQUEST['image'];

        // Prevent the script from timing out
        set_time_limit(0);

        $art_type   = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
        $image      = Art::get_from_source($_SESSION['form']['images'][$image_id], $art_type);
        $dimensions = Core::image_dimensions($image);
        $mime       = $_SESSION['form']['images'][$image_id]['mime'];
        if (!Art::check_dimensions($dimensions)) {
            show_confirmation(T_("There Was a Problem"), T_('Art file failed size check'), $burl);
        } else {
            // Special case for albums, I'm not sure if we should keep it, remove it or find a generic way
            if ($object_type == 'album') {
                $album        = new $object_type($object_id);
                $album_groups = $album->get_group_disks_ids();
                foreach ($album_groups as $a_id) {
                    $art = new Art($a_id, $object_type);
                    $art->insert($image, $mime);
                }
            } else {
                $art = new Art($object_id, $object_type);
                $art->insert($image, $mime);
            }
        }

        header("Location:" . $burl);
        break;
}

// Show the Footer
UI::show_query_stats();
UI::show_footer();
