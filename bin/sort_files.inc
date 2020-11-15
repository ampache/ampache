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
$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

// default various artist album name.
$various_artist = "Various Artists";
$options        = getopt("xv:");

// Require -x to disable test mode
$test_mode = !array_key_exists('x', $options);

if (array_key_exists('v', $options)) {
    echo "\n\t- " . T_('Setting Various Artists name to: ' . $options['n']);
    $various_artist = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $options['n']));
}

/* m(__)m */
$alphabet_prefix = true;

ob_end_clean();

if ($test_mode) {
    echo "\n" . T_('Running in Test Mode. Use -x to execute') . "\n\n";
} else {
    echo "\n***" . T_("WARNING") . "*** " . T_("Running in Write Mode. Make sure you've tested first!") . "\n\n";
}

/* First Clean the catalog to we don't try to write anything we shouldn't */

$sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local'";
$db_results = Dba::read($sql);

while ($row = Dba::fetch_assoc($db_results)) {
    $catalog = Catalog::create_from_id($row['id']);
    /* HINT: Catalog Name */
    printf(T_('Starting Catalog: %s'), stripslashes($catalog->name));
    echo "\n";
    $songs = $catalog->get_songs();

    echo T_('Loaded Catalog') . "\n";

    /* Foreach through each file and find it a home! */
    foreach ($songs as $song) {
        /* Find this poor song a home */
        $song->format();
        // sort_find_home will replace the % with the correct values.
        $directory = sort_find_home($song, $catalog->sort_pattern, $catalog->path, $various_artist);
        $filename  = sort_find_home($song, $catalog->rename_pattern, null, $various_artist);
        if ($directory === false || $filename === false) {
            $fullpath = $song->file;
        } else {
            $fullpath = rtrim($directory, "\/") . "/" . ltrim($filename, "\/") . "." . pathinfo($song->file, PATHINFO_EXTENSION);
        }

        /* We need to actually do the moving (fake it if we are testing)
         * Don't try to move it, if it's already the same friggin thing!
         */
        if ($song->file != $fullpath && strlen($fullpath)) {
            echo T_("Examine File...");
            echo "\n\t";
            /* HINT: filename (File path) */
            printf(T_('Source: %s'), $song->file);
            echo "\n\t";
            /* HINT: filename (File path) */
            printf(T_('Destin: %s'), $fullpath);
            echo "\n";
            flush();
            sort_move_file($song, $fullpath, $test_mode);
        }
    } // end foreach song
} // end foreach catalogs

/************** FUNCTIONS *****************/
/**
 * sort_find_home
 * Get the directory for this file from the catalog and the song info using the sort_pattern
 * takes into account various artists and the alphabet_prefix
 * @param $song
 * @param $sort_pattern
 * @param $base
 * @param string $various_artist
 * @return false|string
 */
function sort_find_home($song, $sort_pattern, $base = null, $various_artist = "Various Artists")
{
    $home = '';
    if ($base) {
        $home = rtrim($base, "\/");
        $home = rtrim($home, "\\");
    }

    /* Create the filename that this file should have */
    $album  = sort_clean_name($song->f_album_full) ?: '%A';
    $artist = sort_clean_name($song->f_artist_full) ?: '%a';
    $track  = sort_clean_name($song->track) ?: '%T';
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
    } elseif ($album_object->artist_count != 1) {
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
    $sort_pattern  = str_replace($replace_array, $content_array, $sort_pattern);

    /* Remove non A-Z0-9 chars */
    $sort_pattern = preg_replace("[^\\\/A-Za-z0-9\-\_\ \'\, \(\)]", "_", $sort_pattern);

    // Replace non-critical search patterns
    $post_replace_array = array('%Y', '%c', '%C', '%r', '%g', '%b', ' []', ' ()');
    $post_content_array = array('', '', '', '', '', '', '', '', '');
    $sort_pattern       = str_replace($post_replace_array, $post_content_array, $sort_pattern);

    $home .= "/$sort_pattern";
    // dont send a mismatched file!
    if (strpos($sort_pattern, '%') !== false) {
        /* HINT: $sort_pattern after replacing %x values */
        printf(T_('The sort_pattern has left over variables. %s'), $sort_pattern);
        echo "\n\n";

        return false;
    }

    return $home;
} // sort_find_home

/**
 * sort_clean_name
 * We have to have some special rules here
 * This is run on every individual element of the search
 * Before it is put together, this removes / and \ and also
 * once I figure it out, it'll clean other stuff
 * @param  string$string
 * @return string|string[]|null
 */
function sort_clean_name($string)
{

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
 * and unlink.. This is a little unsafe, and as such it verifies the copy
 * worked by doing a filesize() before unlinking.
 * @param $song
 * @param $fullname
 * @param $test_mode
 * @return bool
 */
function sort_move_file($song, $fullname, $test_mode)
{
    $old_dir   = dirname($song->file);
    $info      = pathinfo($fullname);
    $directory = $info['dirname'];
    $file      = $info['basename'];
    $data      = preg_split("/[\/\\\]/", $directory);
    $path      = '';

    /* We not need the leading / */
    unset($data[0]);

    foreach ($data as $dir) {
        $dir = sort_clean_name($dir);
        $path .= "/" . $dir;

        /* We need to check for the existence of this directory */
        if (!is_dir($path)) {
            if ($test_mode) {
                echo "\t";
                /* HINT: Directory (File path) */
                printf(T_('Create directory "%s"'), $path);
                echo "\n";
            } else {
                debug_event('sort_files', "Creating $path directory", 4);
                $results = mkdir($path);
                if (!$results) {
                    /* HINT: Directory (File path) */
                    printf(T_("There was a problem creating this directory: %s"), $path);
                    echo "\n";

                    return false;
                }
            } // else we aren't in test mode
        } // if it's not a dir
    } // foreach dir

    /* Now that we've got the correct directory structure let's try to copy it */
    if ($test_mode) {
        echo "\t";
        // HINT: %1$s: file, %2$s: directory
        printf(T_('Copying "%1$s" to "%2$s"'), $file, $directory);
        echo "\n";
        $sql = "UPDATE song SET file='" . Dba::escape($fullname) . "' WHERE id='" . Dba::escape($song->id) . "'";
        echo "\tSQL: $sql\n";
        flush();
    } else {
        /* Check for file existence */
        if (file_exists($fullname)) {
            debug_event('sort_files', 'Error: $fullname already exists', 1);
            /* HINT: filename (File path) */
            printf(T_("Don't overwrite an existing file: %s"), $fullname);
            echo "\n";

            return false;
        }

        $results = copy($song->file, $fullname);
        debug_event('sort_files', 'Copied ' . $song->file . ' to ' . $fullname, 4);

        /* Look for the folder art and copy that as well */
        if (!AmpConfig::get('album_art_preferred_filename') || strstr(AmpConfig::get('album_art_preferred_filename'), "%")) {
            $folder_art  = $directory . DIRECTORY_SEPARATOR . 'folder.jpg';
            $old_art     = $old_dir . DIRECTORY_SEPARATOR . 'folder.jpg';
        } else {
            $folder_art  = $directory . DIRECTORY_SEPARATOR . sort_clean_name(AmpConfig::get('album_art_preferred_filename'));
            $old_art     = $old_dir . DIRECTORY_SEPARATOR . sort_clean_name(AmpConfig::get('album_art_preferred_filename'));
        }

        debug_event('sort_files', 'Copied ' . $old_art . ' to ' . $folder_art, 4);
        if (copy($old_art, $folder_art) === false) {
            throw new RuntimeException('Unable to copy ' . $old_art . ' to ' . $folder_art);
        }

        /* HINT: filename (File path) */
        if (!$results) {
            printf(T_('There was an error trying to copy file to "%s"'), $fullname);
            echo "\n";

            return false;
        }

        /* Check the filesize */
        $new_sum = Core::get_filesize($fullname);
        $old_sum = Core::get_filesize($song->file);

        if ($new_sum != $old_sum || !$new_sum) {
            /* HINT: filename (File path) */
            printf(T_('Size comparison failed. Not deleting: %s'), $song->file);
            echo "\n";

            return false;
        } // end if sum's don't match

        /* If we've made it this far it should be safe */
        $results = unlink($song->file);
        if (!$results) {
            /* HINT: filename (File path) */
            printf(T_('There was an error trying to delete "%s"'), $song->file);
            echo "\n";
        }

        /* Update the catalog */
        $sql = "UPDATE song SET file='" . Dba::escape($fullname) . "' WHERE id='" . Dba::escape($song->id) . "'";
        Dba::write($sql);
    } // end else

    return true;
} // sort_move_file
