<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved

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

/* Don't do anything just tell me what you would do */
//$test_mode = true; 

/* m(__)m */
$alphabet_prefix = true;
 
define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';

/* First Clean the catalog to we don't try to write anything we shouldn't */

$sql = "SELECT id FROM catalog WHERE catalog_type='local'";
$db_results = mysql_query($sql, dbh());

$catalogs = array();

while ($r = mysql_fetch_row($db_results)) { 

	$catalog = new Catalog($r['0']);
	$songs = $catalog->get_catalog_files();

	echo "Starting Catalog: " . stripslashes($catalog->name) . "\n";

	/* Foreach through each file and find it a home! */
	foreach ($songs as $song) { 
		/* Find this poor song a home */
		$song->format_song();
		$directory 	= sort_find_home($song,$catalog->sort_pattern,$catalog->path);
		$filename 	= sort_find_filename($song,$catalog->rename_pattern);
		$fullpath = $directory . "/" . $filename;

		/* Check for Demo Mode */
		if ($test_mode) { 
			/* We're just talking here... no work */
			echo "Moving File...\n\tSource: $song->file\n\tDest: $fullpath\n";
			flush();
		}
		/* We need to actually do the moving (fake it if we are testing) 
		 * Don't try to move it, if it's already the same friggin thing!
		 */
		if ($song->file != $fullpath && strlen($fullpath)) { 
			sort_move_file($song,$fullpath);
		}



	} // end foreach song

} // end foreach catalogs


/************** FUNCTIONS *****************/
/**
 * sort_find_filename 
 * This gets the filename that this file should have, it takes the rename pattern of the catalog
 * along with the song object. Nothing Special Here
 */
function sort_find_filename($song,$rename_pattern) { 
	
	$extension = ltrim(substr($song->file,strlen($song->file)-4,4),".");
	
	/* Create the filename that this file should have */
	$album 	= sort_clean_name($song->f_album_full);
	$artist = sort_clean_name($song->f_artist_full);
	$genre 	= sort_clean_name($song->f_genre);
	$track	= sort_clean_name($song->track);
	$title	= sort_clean_name($song->title);
	$year	= sort_clean_name($song->year);
        
	/* Start replacing stuff */
	$replace_array = array('%a','%A','%t','%T','%y','%g');
	$content_array = array($artist,$album,$title,$track,$year,$genre);
	
	$rename_pattern = str_replace($replace_array,$content_array,$rename_pattern);

	$rename_pattern = preg_replace("[\-\:\!]","_",$rename_pattern);

	return $rename_pattern . "." . $extension;

} // sort_find_filename

/**
 * sort_find_home
 * Get the directory for this file from the catalog and the song info using the sort_pattern 
 * takes into account various artists and the alphabet_prefix
 */
function sort_find_home($song,$sort_pattern,$base) { 

	$home = rtrim($base,"\/");
	$home = rtrim($home,"\\");

	/* Create the filename that this file should have */
	$album 	= sort_clean_name($song->f_album_full);
	$artist = sort_clean_name($song->f_artist_full);
	$genre 	= sort_clean_name($song->f_genre);
	$track	= sort_clean_name($song->track);
	$title	= sort_clean_name($song->title);
	$year	= sort_clean_name($song->year);

	/* Do the various check */
	$album_object = new Album($song->album);
	if ($album_object->artist_count != '1') { 
		$artist = "Various";
	}
	
	/* IF we want a,b,c,d we need to know the first element */
	if ($GLOBALS['alphabet_prefix']) { 
		$sort_pattern = preg_replace("/\/?%o\//","",$sort_pattern);
		$first_element = substr($sort_pattern,0,2);
		$element = sort_element_name($first_element);
		if (!$element) { $alphabet = 'ZZ'; }
		else { 	$alphabet = strtoupper(substr(${$element},0,1)); } 
		$alphabet = preg_replace("/[^A-Za-z0-9]/","ZZ",$alphabet);

		$home .= "/$alphabet";
	}

	/* Replace everything we can find */
	$replace_array = array('%a','%A','%t','%T','%y','%g','%C');
	$content_array = array($artist,$album,$title,$track,$year,$genre,'C');
	$sort_pattern = str_replace($replace_array,$content_array,$sort_pattern);

	/* Remove non A-Z0-9 chars */
	$sort_pattern = preg_replace("[^\\\/A-Za-z0-9\-\_\ \'\,\(\)]","_",$sort_pattern);
	
	$home .= "/$sort_pattern";

	return $home;

} // sort_find_home

/**
 * sort_element_name
 * gets the name of the %? in a yea.. too much beer 
 */
function sort_element_name($key) { 

	switch ($key) { 
		case '%t':
			return 'title';
		break;
		case '%T':
			return 'track';
		break;
		case '%a':
			return 'artist';
		break;
		case '%A':
			return 'album';
		break;
		case '%y':
			return 'year';
		break;
		case '%g':
			return 'genre';
		break;
		default: 
		break;
	} // switch on key

	return false;

} // sort_element_name

/**
 * sort_clean_name
 * We have to have some special rules here
 * This is run on every individual element of the search
 * Before it is put togeather, this removes / and \ and also
 * once I figure it out, it'll clean other stuff
 */
function sort_clean_name($string) { 

	/* First remove any / or \ chars */
	$string = preg_replace('/[\/\\\]/','-',$string);

	$string = str_replace(':',' ',$string);

	$string = preg_replace('/[\!\:\*]/','_',$string);

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
function sort_move_file($song,$fullname) { 

	$old_dir	= dirname($song->file);

	$info = pathinfo($fullname);

	$directory 	= $info['dirname'];
	$file		= $info['basename'];
	$data = preg_split("/[\/\\\]/",$directory);
	$path = '';

	/* We not need the leading / */
	unset($data[0]);

	foreach ($data as $dir) { 
	
		$dir = sort_clean_name($dir);
		$path .= "/" . $dir;

		/* We need to check for the existance of this directory */
		if (!is_dir($path)) { 
			if ($GLOBALS['test_mode']) { 
				echo "\tMaking $path Directory\n";
			}
			else { 
				debug_event('mkdir',"Creating $path directory",'5'); 
				$results = mkdir($path);	
				if (!$results) { 
					echo "Error: Unable to create $path move failed\n";
					return false;
				}
			} // else we aren't in test mode
		} // if it's not a dir

	} // foreach dir

	/* Now that we've got the correct directory structure let's try to copy it */
	if ($GLOBALS['test_mode']) { 
		echo "\tCopying $file to $directory\n";
		$sql = "UPDATE song SET file='" . sql_escape($fullname) . "' WHERE id='" . sql_escape($song->id) . "'";
		echo "\tSQL: $sql\n";
	}
	else { 

		/* Check for file existance */
		if (file_exists($fullname)) { 
			debug_event('file exists','Error: $fullname already exists','1'); 
			echo "Error: $fullname already exists\n"; 
			return false;
		}
	
		$results = copy($song->file,$fullname);
		debug_event('copy','Copied ' . $song->file . ' to ' . $fullname,'5'); 
		
		/* Look for the folder art and copy that as well */
		if (!conf('album_art_preferred_filename')) { 
			$folder_art 	= $directory . '/folder.jpg';
			$old_art 	= $old_dir . '/folder.jpg';
		}
		else { 
			$folder_art 	= $directory . "/" . conf('album_art_preferred_filename'); 
			$old_art 	= $old_dir . "/" . conf('album_art_preferred_filename');
		}

		debug_event('copy_art','Copied ' . $old_art . ' to ' . $folder_art,'5');
		@copy($old_art,$folder_art);
		
		if (!$results) { echo "Error: Unable to copy file to $fullname\n"; return false; }

		/* Check the filesize */
		$new_sum = filesize($fullname);
		$old_sum = filesize($song->file);

		if ($new_sum != $old_sum OR !$new_sum) { 
			echo "Error: Size Inconsistency, not deleting " . $song->file . "\n"; 
			return false; 
		} // end if sum's don't match
		
		/* If we've made it this far it should be safe */
		$results = unlink($song->file);
		if (!$results) { echo "Error: Unable to delete " . $song->file . "\n"; }

		/* Update the catalog */
		$sql = "UPDATE song SET file='" . sql_escape($fullname) . "' WHERE id='" . sql_escape($song->id) . "'";
		$db_results = mysql_query($sql, dbh());

	} // end else

	return true;

} // sort_move_file

?>
