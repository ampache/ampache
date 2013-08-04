<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 * Album Art
 * This pulls album art out of the file using the getid3 library
 * and dumps it to the browser as an image mime type.
 *
 */

// This file is a little weird it needs to allow API session
// this needs to be done a little better, but for now... eah
define('NO_SESSION','1');
require_once 'lib/init.php';

// Check to see if they've got an interface session or a valid API session, if not GTFO
if (!Session::exists('interface', $_COOKIE[Config::get('session_name')]) && !Session::exists('api', $_REQUEST['auth'])) {
    debug_event('image','Access denied, checked cookie session:' . $_COOKIE[Config::get('session_name')] . ' and auth:' . $_REQUEST['auth'], 1);
    exit;
}

// If we aren't resizing just trash thumb
if (!Config::get('resize_images')) { $_GET['thumb'] = null; } 

// FIXME: Legacy stuff - should be removed after a version or so
if (!isset($_GET['object_type'])) { 
    $_GET['object_type'] = 'album'; 
} 

$type = Art::validate_type($_GET['object_type']); 

/* Decide what size this image is */
switch ($_GET['thumb']) {
    case '1':
        /* This is used by the now_playing stuff */
        $size['height'] = '75';
        $size['width']    = '75';
    break;
    case '2':
        $size['height']    = '128';
        $size['width']    = '128';
    break;
    case '3':
        /* This is used by the flash player */
        $size['height']    = '80';
        $size['width']    = '80';
    break;
    case '4':
        /* HTML5 Player size */
        $size['height'] = 200;
        $size['width'] = 200; // 200px width, set via CSS
    break;
    default:
        $size['height'] = '275';
        $size['width']    = '275';
        if (!isset($_GET['thumb'])) { $return_raw = true; }
    break;
} // define size based on thumbnail

switch ($_GET['type']) {
    case 'popup':
        require_once Config::get('prefix') . '/templates/show_big_art.inc.php';
    break;
    // If we need to pull the data out of the session
    case 'session':
        Session::check();
        $filename = scrub_in($_REQUEST['image_index']);
        $image = Art::get_from_source($_SESSION['form']['images'][$filename], 'album');
        $mime = $_SESSION['form']['images'][$filename]['mime'];
    break;
    default:
        $media = new $type($_GET['id']);
        $filename = $media->name;

        $art = new Art($media->id,$type); 
        $art->get_db();  

        if (!$art->raw_mime) {
            $mime = 'image/jpeg';
            $image = file_get_contents(Config::get('prefix') . 
                Config::get('theme_path') .
                '/images/blankalbum.jpg');
        }
        else {
            if ($_GET['thumb']) {
                $thumb_data = $art->get_thumb($size);
            }
                
            $mime = $thumb_data 
                ? $thumb_data['thumb_mime']
                : $art->raw_mime;     
            $image = $thumb_data
                ? $thumb_data['thumb']
                : $art->raw;
        }
    break;
} // end switch type

if ($image) {
    $extension = Art::extension($mime); 
    $filename = scrub_out($filename . '.' . $extension);

    // Send the headers and output the image
    $browser = new Horde_Browser();
    $browser->downloadHeaders($filename, $mime, true);
    echo $image;
}

?>
