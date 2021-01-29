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
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

ob_end_flush();

$options = getopt("hn:f:mvcaguio");

// catalog name and file are mandatory
if (array_key_exists('h', $options) || (!array_key_exists('n', $options) && !array_key_exists('f', $options))) {
    usage();

    return false;
}

$operations_string = '';
if (array_key_exists('n', $options)) {
    $catname = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $options['n']));
}
if (array_key_exists('f', $options)) {
    $catfile = $options['f'];
}
if (array_key_exists('c', $options)) {
    $catclean = 1;
}
if (array_key_exists('v', $options)) {
    $catverify = 1;
}
if (array_key_exists('a', $options)) {
    $catadd = 1;
}
if (array_key_exists('g', $options)) {
    $artadd = 1;
}
if (count($_SERVER['argv']) != 1 && $catname != 1 && $catfile != 1 && $artadd != 1 && $catclean != 1 && $catverify != 1 && $catadd != 1) {
    usage();

    return false;
}

// -------- Options before the File actions loop
if ($artadd == 1) {
    $options['gather_art'] = true;
} else {
    $options['gather_art'] = false;
}

$sql = "SELECT `id` FROM `catalog` WHERE `name` = '$catname' AND `catalog_type`='local'";
$db_results = Dba::read($sql);

ob_start("ob_html_strip",'1024',true);

while ($row = Dba::fetch_assoc($db_results)) {
    $catalog = Catalog::create_from_id($row['id']);
    ob_flush();
    echo "\n";
    if (!$catalog->id) {
        echo "- catalog '$catname' NOT FOUND - \n";

        return false;
    }
    // Identify the catalog and file (if it exists in the DB)
    switch ($catalog->gather_types) {
        case 'podcast':
            $type    = 'podcast_episode';
            $file_id = Catalog::get_id_from_file($catfile, $type);
            $media   = new Podcast_Episode(Catalog::get_id_from_file($catfile, $type));
            break;
        case 'clip':
        case 'tvshow':
        case 'movie':
        case 'personal_video':
            $type    = 'video';
            $file_id = Catalog::get_id_from_file($catfile, $type);
            $media   = new Video($file_id);
            break;
        case 'music':
        default:
            $type    = 'song';
            $file_id = Catalog::get_id_from_file($catfile, $type);
            $media   = new Song($file_id);
            break;
    }
    // deleted file
    if (!is_file($catfile) && $catclean == 1) {
        $catalog->clean_file($catfile, $type);
        /* HINT: File path */
        printf(T_('Removing File: "%s"'), $catfile);

        return false;
    }
    // existing files
    if (is_file($catfile) && Core::is_readable($catfile)) {
        /* HINT: File path */
        printf(T_('Reading File: "%s"'), $catfile);
        if ($media->id && $catverify == 1) {
            // Verify Existing files
            $catalog = $media->catalog;
            Catalog::update_media_from_tags($media);
        }
        // new files don't have an ID
        if (!$file_id && $catadd == 1) {
            // Look for new files
            $catalog->add_file($catfile, array());
            Catalog::get_id_from_file($catfile, $type);
            // get the new id after adding it
            $file_id = Catalog::get_id_from_file($catfile, $type);
        }
        // for songs and videos run an art gather
        if ($artadd == 1 && $file_id) {
            $gather_song_art = (AmpConfig::get('gather_song_art', false));
            if ($type == 'song') {
                $media    = new Song($file_id);
                $art      = ($gather_song_art) ? new Art($file_id, $type) : new Art($media->album, $type);
                $art_id   = ($gather_song_art) ? $file_id : $media->album;
                $art_type = ($gather_song_art) ? 'song' : 'album';
                $artist   = new Art($media->artist, $type);
                if (!$art->has_db_info()) {
                    Catalog::gather_art_item($art_type, $art_id, true);
                }
                if (!$artist->has_db_info()) {
                    Catalog::gather_art_item('artist', $media->artist, true);
                }
            }
            if ($type == 'video') {
                $art = new Art($file_id, $type);
                if (!$art->has_db_info()) {
                    Catalog::gather_art_item($type, $file_id, true);
                }
            }
        }
    }
}

ob_end_flush();
echo "\n";

/**
 * ob_html_strip
 * @param string $string
 * @return string|string[]|null
 */
function ob_html_strip($string)
{
    $string = str_replace('<br />', "\n", $string);
    $string = strip_tags($string);
    $string = html_entity_decode($string);
    $string = preg_replace("/[\r\n]+[\s\t]*[\r\n]+/","\n",$string);
    $string = trim($string);

    return $string;
} // ob_html_strip

function usage()
{
    echo "\n";
    echo "----------------------------------------------------------\n\t\t";
    echo T_("Usage:") . " \n\033[32m";
    echo T_("catalog_update.inc [-n CATALOG NAME] [-f FILE PATH] [-h|-c|-v|-a|-g]") . "\033[0m (\033[31m!\033[0m)" . " \033[0m\n";
    echo "----------------------------------------------------------\n";
    echo "\n-h\t" . T_('This help message');
    echo "\n-n\t" . T_('Catalog Name');
    echo "\n-f\t" . T_('File Path');
    echo "\n-c\t" . T_('Clean File');
    echo "\n-v\t" . T_('Verify File');
    echo "\n-a\t" . T_('Add File');
    echo "\n-g\t" . T_('Gather Art') . " \033[0m\n";
    echo "----------------------------------------------------------\n";
}
