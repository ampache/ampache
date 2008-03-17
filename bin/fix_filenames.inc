<?php
/*

 Copyright 2001 - 2008 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

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
	echo "ERROR: Iconv required for this functionality, quiting\n"; 
	exit; 
} 

$sql = "SELECT * FROM `catalog` WHERE `catalog_type`='local'"; 
$db_results = Dba::query($sql); 

while ($row = Dba::fetch_assoc($db_results)) { 

	charset_directory_correct($row['path']); 	

} // end of the catalogs


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
		echo "ERROR: Unable to open $path\n"; 
		return false; 
	} 

	if (!chdir($path)) { 
		echo "ERROR: Unable to chdir to $path\n"; 
		return false; 
	} 

	while ( false !== ($file = readdir($handle) ) ) { 

		if ($file == '.' || $file == '..') { continue; } 

		$full_file = $path.$slash_type.$file;

		if (is_dir($full_file)) { 
			charset_directory_correct($full_file); 
			continue; 
		} 

		$translated_filename = iconv(Config::get('site_charset'),Config::get('site_charset') . '//IGNORE',$full_file);

		if (strcmp($full_file,$translated_filename) != '0') { 
			echo "--------------------------------------------------------------------------------------------\n";
			echo "OLD:$full_file has invalid chars\nNEW:$translated_filename\n"; 
			echo "--------------------------------------------------------------------------------------------\n";
			if (!$GLOBALS['i_am_crazy']) { 
				echo "Rename File (Y/N):"; 
				$input = trim(fgets(STDIN)); 
				if (strcasecmp($input,'Y') == 0) { charset_rename_file($full_file,$translated_filename); } 
				else { echo "\n\tNot Renaming...\n\n"; } 
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
	// incase our crap char is in the directory
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
				echo "Error: Unable to create $path move failed, stopping\n"; 
				return false; 
			} 
		} // if the dir doesn't exist

	} // end foreach

	// Now to copy the file
	$results = copy($full_file,$translated_filename); 
	
	if (!$results) { 
		echo "Error: Copy Failed, not deleteing old file\n"; 
		return false; 
	} 
	
	$old_sum = filesize($full_file); 
	$new_sum = filesize($translated_filename); 

	if ($old_sum != $new_sum OR !$new_sum) { 
		echo "Error: Size Inconsistency, not deleting" . $full_file . "\n"; 
		return false; 
	} 

	$results = unlink($full_file); 

	if (!$results) { echo "Error: Unable to delete " . $full_file . "\n"; return false; }  
	

	echo "File Moved...\n\n"; 

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
