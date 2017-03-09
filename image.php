<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
    // Check to see if they've got an interface session or a valid API session, if not GTFO
    if (!Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')]) && !Session::exists('api', $_REQUEST['auth'])) {
        debug_event('image','Access denied, checked cookie session:' . $_COOKIE[AmpConfig::get('session_name')] . ' and auth:' . $_REQUEST['auth'], 1);
        exit;
    }
}

// If we aren't resizing just trash thumb
if (!AmpConfig::get('resize_images')) {
    $_GET['thumb'] = null;
}

// FIXME: Legacy stuff - should be removed after a version or so
if (!isset($_GET['object_type'])) {
    $_GET['object_type'] = 'album';
}

$type = $_GET['object_type'];
if (!Art::is_valid_type($type)) {
    exit;
}

/* Decide what size this image is */
$size = Art::get_thumb_size($_GET['thumb']);
$kind = isset($_GET['kind']) ? $_GET['kind'] : 'default';

$image       = '';
$mime        = '';
$filename    = '';
$etag        = '';
$typeManaged = false;
if (isset($_GET['type'])) {
    switch ($_GET['type']) {
        case 'popup':
            $typeManaged = true;
            require_once AmpConfig::get('prefix') . UI::find_template('show_big_art.inc.php');
        break;
        case 'session':
            // If we need to pull the data out of the session
            Session::check();
            $filename    = scrub_in($_REQUEST['image_index']);
            $image       = Art::get_from_source($_SESSION['form']['images'][$filename], 'album');
            $mime        = $_SESSION['form']['images'][$filename]['mime'];
            $typeManaged = true;
        break;
    }
}
if (!$typeManaged) {
    $item     = new $type($_GET['object_id']);
    $filename = $item->name ?: $item->title;

    $art = new Art($item->id, $type, $kind);
    $art->get_db();
    $etag = $art->id;

    // That means the client has a cached version of the image
    $reqheaders = getallheaders();
    if (isset($reqheaders['If-Modified-Since']) && isset($reqheaders['If-None-Match'])) {
        $ccontrol = $reqheaders['Cache-Control'];
        if ($ccontrol != 'no-cache') {
            $cetagf = explode('-', $reqheaders['If-None-Match']);
            $cetag  = $cetagf[0];
            // Same image than the cached one? Use the cache.
            if ($cetag == $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
    }

    if (!$art->raw_mime) {
        $rootimg = AmpConfig::get('prefix') . AmpConfig::get('theme_path') . '/images/';
        switch ($type) {
            case 'video':
            case 'tvshow':
            case 'tvshow_season':
                $mime       = 'image/png';
                $defaultimg = AmpConfig::get('custom_blankmovie');
                if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                    $defaultimg = $rootimg . "blankmovie.png";
                }
                break;
            default:
                $mime       = 'image/png';
                $defaultimg = AmpConfig::get('custom_blankalbum');
                if (empty($defaultimg) || (strpos($defaultimg, "http://") !== 0 && strpos($defaultimg, "https://") !== 0)) {
                    $defaultimg = $rootimg . "blankalbum.png";
                }
            break;
        }
        $image = file_get_contents($defaultimg);
    } else {
        if ($_GET['thumb']) {
            $thumb_data = $art->get_thumb($size);
            $etag .= '-' . $_GET['thumb'];
        }

        $mime  = isset($thumb_data['thumb_mime']) ? $thumb_data['thumb_mime'] : $art->raw_mime;
        $image = isset($thumb_data['thumb']) ? $thumb_data['thumb'] : $art->raw;
    }
}

if (!empty($image)) {
    $extension = Art::extension($mime);
    $filename  = scrub_out($filename . '.' . $extension);

    // Send the headers and output the image
    $browser = new Horde_Browser();
    if (!empty($etag)) {
        header('ETag: ' . $etag);
        header('Cache-Control: private');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
    }
    header("Access-Control-Allow-Origin: *");
    $browser->downloadHeaders($filename, $mime, true);
    echo $image;
}
