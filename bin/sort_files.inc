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

/**
 * Still not fully tested but "Appears to work" use at your own risk
 * sort_files
 * This script has a lot of stuff to worry about. It's primary duty is to re-organize
 * your files based on some sane, and predefined (in the interface) order using the
 * tag information gathered and updated in ampache. Sort_Pattern defines the directory
 * structure and rename_pattern defines the file pattern. This script should allow you
 * to do both or neither. Oooh and allow you to sort with A,B,C,D prefix
 *
 * Attempt 1 - Do each file one by one and satisfy the needs of each file by its self (this is going to be slow)
 * Cache information so we don't have to check for every file!
 */

define('NO_SESSION', '1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

// default various artist album name.
$various_artist = "Various Artists";

// Don't do anything by default just tell me what you would do
$test_mode = true;

$options = getopt("xv:");
if (array_key_exists('x', $options)) {
    $test_mode = false;
}
if (array_key_exists('v', $options)) {
    $operations_string .= "\n\t- " . T_('Setting catalog name');
    $various_artist = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $options['n']));
}

/* m(__)m */
$alphabet_prefix = true;

ob_end_clean();

if ($test_mode) {
    echo "\n". T_('Running in Test Mode. Use -x to execute') . "\n\n";
} else {
    echo "\n***" . T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!") . "\n\n";
}

/* First Clean the catalog to we don't try to write anything we shouldn't */

$sql = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local'";
$db_results = Dba::read($sql);

$catalogs = array();

while ($r = Dba::fetch_row($db_results)) {

    $catalog = Catalog::create_from_id($r[0]);
    printf (T_('Starting Catalog: %s'), stripslashes($catalog->name));
    echo "\n";
    $songs = $catalog->get_songs();

    echo T_('Loaded Catalog') . "\n";

    /* Foreach through each file and find it a home! */
    foreach ($songs as $song) {
        /* Find this poor song a home */
        $song->format();
        // sort_find_home will replace the % with the correct values.
        $directory = sort_find_home($song, $catalog->sort_pattern, $catalog->path);
        $filename  = sort_find_home($song, $catalog->rename_pattern, null);
        if ($directory == false || $filename == false) {
            $fullpath = $song->file;
        } else {
            $fullpath = rtrim($directory, "\/") . "/" . ltrim($filename, "\/") . "." . pathinfo($song->file, PATHINFO_EXTENSION);;
        }

        /* Check for Demo Mode */
        if ($test_mode) {
            /* We're just talking here... no work */
            echo T_("Examine File...");
            echo "\n\t";
            printf (T_('Source: %s'), $song->file);
            echo "\n\t";
            printf (T_('Destin: %s'), $fullpath);
            echo "\n";
            flush();
        }
        /* We need to actually do the moving (fake it if we are testing)
         * Don't try to move it, if it's already the same friggin thing!
         */
        if ($song->file != $fullpath && strlen($fullpath)) {
            sort_move_file($song, $fullpath);
        }

    } // end foreach song

} // end foreach catalogs

/************** FUNCTIONS *****************/
/**
 * sort_find_home
 * Get the directory for this file from the catalog and the song info using the sort_pattern
 * takes into account various artists and the alphabet_prefix
 */
function sort_find_home($song, $sort_pattern, $base = null) {

    $home = '';
    if ($base) {
        $home = rtrim($base, "\/");
        $home = rtrim($home, "\\");
    }

    /* Create the filename that this file should have */
    $album   = sort_clean_name($song->f_album_full) ?: '%A';
    $artist  = sort_clean_name($song->f_artist_full) ?: '%a';
    $track   = sort_clean_name($song->track) ?: '%T';
    if ((int) $track < 10 && (int) $track > 0) {
        $track = '0' . (string) $track;
    }

    $title   = sort_clean_name($song->title) ?: '%t';
    $year    = sort_clean_name($song->year) ?: '%y';
    $comment = sort_clean_name($song->comment) ?: '%c';

    /* Do the various check */
    $album_object = new Album($song->album);
    $album_object->format();
    if ($album_object->f_album_artist_name) {
        $artist = $album_object->f_album_artist_name;
    } else if ($album_object->artist_count != 1) {
        $artist = $various_artist;
    }
    $disk           = $album_object->disk ?: '%d';
    $catalog_number = $album_object->catalog_number ?: '%C';
    $barcode        = $album_object->barcode ?: '%b';
    $original_year  = $album_object->original_year ?: '%Y';
    $genre          = implode("; ", $album_object->tags) ?: '%b';
    $release_type   = $album_object->release_type ?: '%r';

    /* Replace everything we can find */
    $replace_array = array('%a', '%A', '%t', '%T', '%y', '%Y', '%c', '%C', '%r', '%d', '%g', '%b');
    $content_array = array($artist, $album, $title, $track, $year, $original_year, $comment, $catalog_number, $release_type, $disk, $genre, $barcode);
    $sort_pattern = str_replace($replace_array, $content_array, $sort_pattern);

    /* Remove non A-Z0-9 chars */
    $sort_pattern = preg_replace("[^\\\/A-Za-z0-9\-\_\ \'\, \(\)]", "_", $sort_pattern);

    // Replace non-critical search patterns
    $post_replace_array = array('%Y', '%c', '%C', '%r', '%g', '%b', ' []', ' ()');
    $post_content_array = array('', '', '', '', '', '', '', '', '');
    $sort_pattern = str_replace($post_replace_array, $post_content_array, $sort_pattern);

    $home .= "/$sort_pattern";
    // dont send a mismatched file!
    if (strpos($sort_pattern, '%') !== false) {
        echo "\tERROR with destination: " . $sort_pattern . "\n\n";
        return false;
    }

    return $home;

} // sort_find_home

/**
 * sort_clean_name
 * We have to have some special rules here
 * This is run on every individual element of the search
 * Before it is put togeather, this removes / and \ and also
 * once I figure it out, it'll clean other stuff
 */
function sort_clean_name($string) {

    /* First remove any / or \ chars */
    $string = preg_replace('/[\/\\\]/', '-', $string);

    $string = str_replace(':', ' ', $string);

    $string = preg_replace('/[\!\:\*]/', '_', $string);

    return $string;

} // sort_clean_name

/**
 * sort_move_file
 * All this function does is, move the friggin file and then update the database
 * We can't use the rename() function of PHP because it's functionality depends on the
 * current phase of the moon, the alignment of the planets and my current BAL
 * Instead we cheeseball it and walk through the new dir structure and make
 * sure that the directories exist, once the dirs exist then we do a copy
 * and unlink.. This is a little unsafe, and as such it verifys the copy
 * worked by doing a filesize() before unlinking.
 */
function sort_move_file($song, $fullname) {

    $old_dir    = dirname($song->file);

    $info = pathinfo($fullname);

    $directory     = $info['dirname'];
    $file        = $info['basename'];
    $data = preg_split("/[\/\\\]/", $directory);
    $path = '';

    /* We not need the leading / */
    unset($data[0]);

    foreach ($data as $dir) {

        $dir = sort_clean_name($dir);
        $path .= "/" . $dir;

        /* We need to check for the existance of this directory */
        if (!is_dir($path)) {
            if (Core::get_global('test_mode')) {
                echo "\t";
                printf (T_('Making: %s Directory'), $path);
                echo "\n";
            }
            else {
                debug_event('sort_files.inc', "Creating $path directory", 4);
                $results = mkdir($path);
                if (!$results) {
                    printf (T_('Error: Unable to create %s move failed'), $path);
                    echo "\n";
                    return false;
                }
            } // else we aren't in test mode
        } // if it's not a dir

    } // foreach dir

    /* Now that we've got the correct directory structure let's try to copy it */
    if (Core::get_global('test_mode')) {
        echo "\t";
        // HINT: %1$s: file, %2$s: directory
        printf (T_('Copying %1$s to %2$s'), $file, $directory);
        echo "\n";
        $sql = "UPDATE song SET file='" . Dba::escape($fullname) . "' WHERE id='" . Dba::escape($song->id) . "'";
        echo "\tSQL: $sql\n";
        flush();
    }
    else {

        /* Check for file existance */
        if (file_exists($fullname)) {
            debug_event('sort_files.inc', 'Error: $fullname already exists', 1);
            printf (T_('Error: %s already exists'), $fullname);
            echo "\n";
            return false;
        }

        $results = copy($song->file, $fullname);
        debug_event('sort_files.inc', 'Copied ' . $song->file . ' to ' . $fullname, 4);

        /* Look for the folder art and copy that as well */
        if (!AmpConfig::get('album_art_preferred_filename') || strstr(AmpConfig::get('album_art_preferred_filename'), "%")) {
            $folder_art     = $directory . DIRECTORY_SEPARATOR . 'folder.jpg';
            $old_art     = $old_dir . DIRECTORY_SEPARATOR . 'folder.jpg';
        }
        else {
            $folder_art     = $directory . DIRECTORY_SEPARATOR . sort_clean_name(AmpConfig::get('album_art_preferred_filename'));
            $old_art     = $old_dir . DIRECTORY_SEPARATOR . sort_clean_name(AmpConfig::get('album_art_preferred_filename'));
        }

        debug_event('sort_files.inc', 'Copied ' . $old_art . ' to ' . $folder_art, 4);
        if (copy($old_art, $folder_art) === false) {
            throw new \RuntimeException('Unable to copy ' . $old_art . ' to ' . $folder_art);
        }

        if (!$results) { printf (T_('Error: Unable to copy file to %s'), $fullname); echo "\n"; return false; }

        /* Check the filesize */
        $new_sum = Core::get_filesize($fullname);
        $old_sum = Core::get_filesize($song->file);

        if ($new_sum != $old_sum || !$new_sum) {
            printf (T_('Error: Size Inconsistency, not deleting %s'), $song->file);
            echo "\n";
            return false;
        } // end if sum's don't match

        /* If we've made it this far it should be safe */
        $results = unlink($song->file);
        if (!$results) { printf (T_('Error: Unable to delete %s'), $song->file); echo "\n"; }

        /* Update the catalog */
        $sql = "UPDATE song SET file='" . Dba::escape($fullname) . "' WHERE id='" . Dba::escape($song->id) . "'";
        $db_results = Dba::write($sql);

    } // end else

    return true;

} // sort_move_file
