<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
require_once($prefix . '/lib/init.php');

$valid_exts = array ('m3u', 'xspf', 'pls');

if (!Core::get_global('argv')[1] || Core::get_global('argv')[1] == '-h') { usage(); }
else {
    $dirname    = rtrim(Core::get_global('argv')[1], "\/");
    $type       = Core::get_global('argv')[2];
    $ext        = Core::get_global('argv')[3];
    if (!$ext || !in_array($ext, $valid_exts)) {
        $ext = 'm3u';
    }
    $url = Core::get_global('argv')[4];
    if (!$url || !$url == 'web') {
        $url = false;
    }
    $user = Core::get_global('argv')[5];
    if (!$url || ! (int) $user > 0 ) {
        $url = -1;
    }
}

// Make sure the output dir is valid and writeable
if (!is_writeable($dirname)) {
    printf (T_('Error: Directory %s is not writable'), $dirname);
    echo "\n"; 
}

// Switch on the type of playlist dump we want to do 
// here
switch ($type) {
    case 'albums':
        $ids = Catalog::get_albums();
        $items = array();
        foreach ($ids as $albumid) {
            $items[] = new Album($albumid);
        }
    break;
    case 'artists':
        $items = Catalog::get_artists();
    break;
    default:
        $ids = Playlist::get_playlists(true, $user);
        $items = array();
        foreach ($ids as $playlistid) {
            $items[] = new Playlist($playlistid);
        }
    break;
} // end type switch

foreach ($items as $item) {
    $item->format();
    $name = $item->get_fullname();
    // We don't know about file system encoding / specificity
    // For now, we only keep simple characters to be sure it will work everywhere
    $name = preg_replace('/[:]/', '.', $name);
    $name = preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
    $filename = $dirname . DIRECTORY_SEPARATOR . $item->id . '. ' . $name . '.' . $ext;
    $medias = $item->get_medias();
    $pl = new Stream_Playlist(-1);
    $pl->title = $item->get_fullname();
    foreach ($medias as $media) {
        if ($url) {
            // use the play url
            $web_array  = Stream_Playlist::media_to_url($media, null, 'web');
            $pl->urls[] = $web_array;
            echo $web_array;
        } else {
            // or the local file
            $pl->urls[] = Stream_Playlist::media_to_url($media, $dirname, 'file');
        }
    }

    $plstr = $pl->{'get_' . $ext . '_string'}();
    if (file_put_contents($filename, $plstr) === false) {
        echo "Error: cannot write playlist file `" . $filename . "`.\n";
    } else {
        echo "Playlist file `" . $filename . "` created.\n";
    }
}

echo "Playlist export completed.\n";

/* FUNCTIONS */
function usage() {
    $desc1 = T_("This will dump a collection of playlists files based on type");
    $desc2 = T_("Types");
    $desc3 = sprintf(T_('Dump all %s into individual files'), T_("Playlists"));
    $desc4 = sprintf(T_('Dump all %s into individual files'), T_("Albums"));
    $desc5 = sprintf(T_('Dump all %s into individual files'), T_("Artists"));
    $desc6 = T_("Extensions: m3u (default), xspf, pls");
    $desc7 = T_("Local file or the play url: file (default), web");
    $desc7 = T_('User ID');

    $string = "write_playlists.php.inc [-h] <DIRNAME> <TYPE> [<EXT> <ITEM_TYPE> <USER_ID>]

        $desc1
        $desc2
            default       $desc3
            albums        $desc4
            artists       $desc5
        $desc6
        $desc7
        $desc8\n\n";

        echo $string;

        return;
} // usage
