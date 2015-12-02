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

define('NO_SESSION','1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

ob_end_clean();

/*
 * Pull the root path of your catalogs one by one
 * and then do a directory sweep and check all of the files
 * that would be cataloged and see if they have the correct charset
 * if they don't prompt for a rename, unless $i_am_crazy is true then just
 * do it
 */

// If set to true / 1 then it will not prompt!
//$GLOBALS['i_am_crazy'] = true;

if (!function_exists('iconv')) {
    echo T_("ERROR: Iconv required for this functionality, quiting");
    echo "\n";
    exit;
}

// Attempt to figure out what the System Charset is
$source_encoding = iconv_get_encoding('output_encoding');

// Attempt a simple translation
$string = sprintf(T_('%s For the Love of Music'), iconv(AmpConfig::get('site_charset'),AmpConfig::get('site_charset')));
echo T_('Testing Basic Translation, the two strings below should look the same');
echo "\n";
echo T_('Original: For the Love of Music');
echo "\n";
printf (T_('Translated: %s'), $string);
echo "\n";
echo "---------------------------------------------------------------------\n";
printf (T_('Input Charset (%s):'), $source_encoding);
$input = trim(fgets(STDIN));

if (strlen($input) > 0) { $source_encoding = trim($input); }
printf (T_('Using %s as source character set'), $source_encoding);
echo "\n";

$sql = "SELECT id FROM `catalog` WHERE `catalog_type`='local'";
$db_results = Dba::read($sql);

while ($row = Dba::fetch_assoc($db_results)) {

    $catalog = Catalog::create_from_id($row['0']);
    printf(T_('Checking %s (%s)'), $catalog->name, $catalog->path);
    echo "\n";
    charset_directory_correct($catalog->path);

} // end of the catalogs

echo T_('Finished checking file names for valid characters');
echo "\n";

/**************************************************
 ****************** FUNCTIONS *********************
 **************************************************/
/**
 * charset_directory_correct
 * This function calls its self recursivly
 * and corrects all of the non-matching filenames
 * it looks at the i_am_crazy var and if not set prompts for change
 */
function charset_directory_correct($path) {

    // Correctly detect the slash we need to use here
        if (strstr($path,"/")) {
            $slash_type = '/';
    }
        else {
            $slash_type = '\\';
    }

    /* Open up the directory */
        $handle = opendir($path);

    if (!is_resource($handle)) {
        printf (T_('ERROR: Unable to open %s'), $path);
        echo "\n";
        return false;
    }

    if (!chdir($path)) {
        printf (T_('ERROR: Unable to chdir to %s'), $path);
        echo "\n";
        return false;
    }

    while ( false !== ($file = readdir($handle) ) ) {

        if ($file == '.' || $file == '..') { continue; }

        $full_file = $path.$slash_type.$file;

        if (is_dir($full_file)) {
            charset_directory_correct($full_file);
            continue;
        }

        $verify_filename = iconv(AmpConfig::get('site_charset'),AmpConfig::get('site_charset') . '//IGNORE',$full_file);

        if (strcmp($full_file,$verify_filename) != '0') {
            $translated_filename = iconv($source_encoding,AmpConfig::get('site_charset') . '//TRANSLIT',$full_file);

            // Make sure the extension stayed the same
            if (substr($translated_filename,strlen($translated_filename)-3,3) != substr($full_file,strlen($full_file)-3,3)) {
                echo T_("Translation failure, stripping non-valid characters");
                echo "\n";
                $translated_filename = iconv($source_encoding,AmpConfig::get('site_charset') . '//IGNORE',$full_file);
            }

            printf (T_('Attempting to Transcode to %s'), AmpConfig::get('site_charset'));
            echo "\n";
            echo "--------------------------------------------------------------------------------------------\n";
            printf (T_('OLD: %s has invalid chars'), $full_file);
            echo "\n";
            printf (T_('NEW: %s'), $translated_filename);
            echo "\n";
            echo "--------------------------------------------------------------------------------------------\n";
            if (!$GLOBALS['i_am_crazy']) {
                echo T_("Rename File (Y/N):");
                $input = trim(fgets(STDIN));
                if (strcasecmp($input,'Y') == 0) { charset_rename_file($full_file,$translated_filename); }
                else { echo "\n\t"; echo T_('Not Renaming...'); echo "\n\n"; }
            }
            else {
                charset_rename_file($full_file,$translated_filename);
            }
        }

    } // while reading file

} // charset_directory_correct

/**
 * charset_rename_file
 * This just takes a source / dest and does the renaming
 */
function charset_rename_file($full_file,$translated_filename) {

    // First break out the base directory name and make sure it exists
    // in case our crap char is in the directory
    $directory = dirname($translated_filename);
    $data = preg_split("/[\/\\\]/",$directory);
    $path = '';

    foreach ($data as $dir) {

        $dir = charset_clean_name($dir);
        $path .= "/" . $dir;

        if (!is_dir($path)) {
            echo "\tMaking $path directory\n";
            $results = mkdir($path);
            if (!$results) {
                printf (T_('Error: Unable to create %s move failed, stopping'), $path);
                echo "\n";
                return false;
            }
        } // if the dir doesn't exist

    } // end foreach

    // Now to copy the file
    $results = copy($full_file,$translated_filename);

    if (!$results) {
        echo T_('Error: Copy Failed, not deleting old file');
        echo "\n";
        return false;
    }

    $old_sum = Core::get_filesize($full_file);
    $new_sum = Core::get_filesize($translated_filename);

    if ($old_sum != $new_sum OR !$new_sum) {
        printf (T_('Error: Size Inconsistency, not deleting %s'), $full_file);
        echo "\n";
        return false;
    }

    $results = unlink($full_file);

    if (!$results) { printf (T_('Error: Unable to delete %s'), $full_file); echo "\n"; return false; }

    echo T_("File Moved...");
    echo "\n\n";

    return true;

} // charset_rename_file

/**
 * charset_clean_name
 * We have to have some special rules here
 * This is run on every individual element of the search
 * Before it is put togeather, this removes / and \ and also
 * once I figure it out, it'll clean other stuff
 */
function charset_clean_name($string) {

        /* First remove any / or \ chars */
        $string = preg_replace('/[\/\\\]/','-',$string);

        $string = str_replace(':',' ',$string);

        $string = preg_replace('/[\!\:\*]/','_',$string);

        return $string;

} // charset_clean_name

?>
