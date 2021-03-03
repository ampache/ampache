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
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

ob_end_clean();

/**
 * Pull the root path of your catalogs one by one
 * and then do a directory sweep and check all of the files
 * that would be cataloged and see if they have the correct charset
 * if they don't prompt for a rename, unless $i_am_crazy is true then just
 * do it
 */

// If set to true / 1 then it will not prompt!
//Core::get_global('i_am_crazy') = true;

if (!function_exists('iconv')) {
    echo T_("php-iconv is required for this functionality, quitting");
    echo "\n";

    return false;
}

// Attempt to figure out what the System Charset is
$source_encoding = iconv_get_encoding('output_encoding');

// Attempt a simple translation
$string = sprintf(T_('%s'), iconv(AmpConfig::get('site_charset'),AmpConfig::get('site_charset'), T_('For the Love of Music')));
echo T_('Testing basic translation, the two strings below should look the same') . "\n";
echo T_('Original: For the Love of Music') . "\n";
printf(
    /* HINT: Translated version of 'For the Love of Music' */
    T_('Translated: %s'), $string);
echo "\n";
echo "---------------------------------------------------------------------\n";
printf(T_('Input Charset (%s):'), $source_encoding);
$input = trim(fgets(STDIN));

if (strlen($input) > 0) {
    $source_encoding = trim($input);
}
printf(T_('Using %s as source character set'), $source_encoding);
echo "\n";

$sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local'";
$db_results = Dba::read($sql);

while ($row = Dba::fetch_assoc($db_results)) {
    $catalog = Catalog::create_from_id($row['id']);
    /* HINT: %1 Catalog Name, %2 Catalog Path */
    printf(T_('Checking %1$s (%2$s)'), $catalog->name, $catalog->path);
    echo "\n";
    charset_directory_correct($catalog->path);
} // end of the catalogs

echo T_('Finished checking file names for valid characters') . "\n";

/**************************************************
 ****************** FUNCTIONS *********************
 **************************************************/
/**
 * charset_directory_correct
 * This function calls its self recursively
 * and corrects all of the non-matching filenames
 * it looks at the i_am_crazy var and if not set prompts for change
 * @param string $path
 * @return boolean
 */
function charset_directory_correct($path)
{
    $source_encoding = iconv_get_encoding('output_encoding');

    // Correctly detect the slash we need to use here
    if (strstr($path,"/")) {
        $slash_type = '/';
    } else {
        $slash_type = '\\';
    }

    /* Open up the directory */
    $handle = opendir($path);

    if (!is_resource($handle)) {
        /* HINT: directory (File path) */
        printf(T_('There was an error trying to open "%s"'), $path);
        echo "\n";

        return false;
    }

    if (!chdir($path)) {
        /* HINT: directory (File path) */
        printf(T_('There was an error trying to chdir to "%s"'), $path);
        echo "\n";

        return false;
    }

    while (false !== ($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $full_file = $path . $slash_type . $file;

        if (is_dir($full_file)) {
            charset_directory_correct($full_file);
            continue;
        }

        $verify_filename = iconv(AmpConfig::get('site_charset'),AmpConfig::get('site_charset') . '//IGNORE',$full_file);

        if (strcmp($full_file,$verify_filename) != 0) {
            $translated_filename = iconv($source_encoding,AmpConfig::get('site_charset') . '//TRANSLIT',$full_file);

            // Make sure the extension stayed the same
            if (substr($translated_filename,strlen($translated_filename) - 3,3) != substr($full_file,strlen($full_file) - 3,3)) {
                echo T_("Translation failure, stripping non-valid characters");
                echo "\n";
                $translated_filename = iconv($source_encoding,AmpConfig::get('site_charset') . '//IGNORE',$full_file);
            }

            printf(T_('Attempting to Transcode to "%s"'), AmpConfig::get('site_charset'));
            echo "\n--------------------------------------------------------------------------------------------\n";
            printf(T_('OLD: "%s" has invalid chars'), $full_file);
            echo "\n";
            printf(T_('NEW: %s'), $translated_filename);
            echo "\n--------------------------------------------------------------------------------------------\n";
            if (!Core::get_global('i_am_crazy')) {
                echo T_("Rename File (Y/N):");
                $input = trim(fgets(STDIN));
                if (strcasecmp((string) $input,'Y') == 0) {
                    charset_rename_file($full_file,$translated_filename);
                } else {
                    echo "\n\t";
                    echo T_('Not renaming...');
                    echo "\n\n";
                }
            } else {
                charset_rename_file($full_file,$translated_filename);
            }
        }
    } // while reading file

    return true;
} // charset_directory_correct

/**
 * charset_rename_file
 * This just takes a source / dest and does the renaming
 * @param string $full_file
 * @param string $translated_filename
 * @return boolean
 */
function charset_rename_file($full_file, $translated_filename)
{

    // First break out the base directory name and make sure it exists
    // in case our crap char is in the directory
    $directory = dirname($translated_filename);
    $data      = preg_split("/[\/\\\]/",$directory);
    $path      = '';

    foreach ($data as $dir) {
        $dir = charset_clean_name($dir);
        $path .= "/" . $dir;

        if (!is_dir($path)) {
            /* HINT: filename (Folder path) */
            printf(T_('Making directory: %s'), $path);
            $results_mkdir = mkdir($path);
            if (!$results_mkdir) {
                /* HINT: filename (File path) */
                printf(T_('There was an error trying to create "%s": Move failed, stopping'), $path);
                echo "\n";

                return false;
            }
        } // if the dir doesn't exist
    } // end foreach

    // Now to copy the file
    $results_copy = copy($full_file,$translated_filename);

    if (!$results_copy) {
        echo T_('File copy failed. Not deleting source file');
        echo "\n";

        return false;
    }

    $old_sum = Core::get_filesize($full_file);
    $new_sum = Core::get_filesize($translated_filename);

    if ($old_sum != $new_sum || !$new_sum) {
        /* HINT: filename (File path) */
        printf(T_('Size comparison failed. Not deleting "%s"'), $full_file);
        echo "\n";

        return false;
    }

    $results = unlink($full_file);

    if (!$results) {
        printf(T_('There was an error trying to delete "%s"'), $full_file);
        echo "\n";

        return false;
    }

    echo T_("File moved...") . "\n\n";

    return true;
} // charset_rename_file

/**
 * charset_clean_name
 * We have to have some special rules here
 * This is run on every individual element of the search
 * Before it is put together, this removes / and \ and also
 * once I figure it out, it'll clean other stuff
 * @param string $string
 * @return string|string[]|null
 */
function charset_clean_name($string)
{
    /* First remove any / or \ chars */
    $string_1 = preg_replace('/[\/\\\]/','-',$string);
    $string_2 = str_replace(':',' ',$string_1);

    return preg_replace('/[\!\:\*]/','_',$string_2);
} // charset_clean_name
