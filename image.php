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

/**
 * Album Art
 * This pulls album art out of the file using the getid3 library
 * and dumps it to the browser as an image mime type.
 *
 */

// This file is a little weird it needs to allow API session
// this needs to be done a little better, but for now... eah
define('NO_SESSION', '1');
define('OUTDATED_DATABASE_OK', 1);
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

if (AmpConfig::get('use_auth') && AmpConfig::get('require_session')) {
    // Check to see if they've got an interface session or a valid API session, if not GTFO
    $token_check = Auth::token_check(Core::get_request('u'), Core::get_request('t'), Core::get_request('s'));
    if (!Session::exists('interface', $_COOKIE[AmpConfig::get('session_name')]) && !Session::exists('api', Core::get_request('auth')) && !empty($token_check)) {
        debug_event('image', 'Access denied, checked cookie session:' . $_COOKIE[AmpConfig::get('session_name')], 2);

        return false;
    }
}

// If we aren't resizing just trash thumb
if (!AmpConfig::get('resize_images')) {
    $_GET['thumb'] = null;
}

// FIXME: Legacy stuff - should be removed after a version or so
if (!filter_has_var(INPUT_GET, 'object_type')) {
    $_GET['object_type'] = (AmpConfig::get('show_song_art')) ? 'song' : 'album';
}

$type = Core::get_get('object_type');
if (!Art::is_valid_type($type)) {
    debug_event('image', 'INVALID TYPE: ' . $type, 4);

    return false;
}

/* Decide what size this image is */
$size = Art::get_thumb_size(filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_NUMBER_INT));
$kind = filter_has_var(INPUT_GET, 'kind') ? filter_input(INPUT_GET, 'kind', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) : 'default';

$image       = '';
$mime        = '';
$filename    = '';
$etag        = '';
$typeManaged = false;
if (filter_has_var(INPUT_GET, 'type')) {
    switch (filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) {
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
    $item     = new $type(filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT));
    $filename = $item->name ?: $item->title;

    $art = new Art($item->id, $type, $kind);
    $art->has_db_info();
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

                return false;
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
        if (filter_has_var(INPUT_GET, 'thumb')) {
            $thumb_data = $art->get_thumb($size);
            $etag .= '-' . filter_input(INPUT_GET, 'thumb', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
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
