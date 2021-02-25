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
require_once($prefix . '/lib/init.php');

$valid_exts = array('m3u', 'xspf', 'pls');

if (!Core::get_global('argv')[1] || Core::get_global('argv')[1] == '-h') {
    write_plist_usage();
} else {
    $dirname    = Core::get_global('argv')[1];
    $type       = Core::get_global('argv')[2];
    $ext        = Core::get_global('argv')[3];
    if (!$ext || !in_array($ext, $valid_exts)) {
        $ext = 'm3u';
    }
}

// Make sure the output dir is valid and writeable
if (!is_writeable($dirname)) {
    /* HINT: Directory (File path) */
    printf(T_("There was a problem creating this directory: %s"), $dirname);
    echo "\n";
}

// Switch on the type of playlist dump we want to do
// here
switch ($type) {
    case 'albums':
        $ids   = Catalog::get_albums();
        $items = array();
        foreach ($ids as $albumid) {
            $items[] = new Album($albumid);
        }
    break;
    case 'artists':
        $items = Catalog::get_artists();
    break;
    default:
        $ids   = Playlist::get_playlists(-1);
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
    $name      = preg_replace('/[:]/', '.', $name);
    $name      = preg_replace('/[^a-zA-Z0-9. -]/', '', $name);
    $filename  = $dirname . DIRECTORY_SEPARATOR . $item->id . '. ' . $name . '.' . $ext;
    $medias    = $item->get_medias();
    $pl        = new Stream_Playlist(-1);
    $pl->title = $item->get_fullname();
    foreach ($medias as $media) {
        $pl->urls[] = Stream_Playlist::media_to_url($media, $dirname, 'file');
    }

    $plstr = $pl->{'get_' . $ext . '_string'}();
    if (file_put_contents($filename, $plstr) === false) {
        /* HINT: playlist (file path) */
        sprintf(T_('There was a problem creating the playlist file "%s"'), $path);
        echo "\n";
    } else {
        /* HINT: playlist (file path) */
        sprintf(T_('Playlist created "%s"'), $path);
        echo "\n";
    }
}

echo "Playlist export completed.\n";

/* FUNCTIONS */
function write_plist_usage()
{
    $desc1 = T_("This will dump a collection of playlists files based on type");
    $desc2 = T_("Playlists");
    $desc3 = T_("Dumps all of your playlists");
    $desc4 = T_("Dumps all Albums as individual playlists");
    $desc5 = T_("Dumps all Artists as individual playlists");
    $desc6 = T_("Extensions: m3u (default), xspf, pls");

    $string = "write_playlists.php.inc [-h] <DIRNAME> <TYPE> [<EXT>]

        $desc1
        $desc2
            default       $desc3
            albums        $desc4
            artists       $desc5
        $desc6\n\n";

    echo $string;
} // usage
