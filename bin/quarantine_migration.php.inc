<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

 created by RosenSama 2005
	
 call: php quarantine_migration.php	
*/

$no_session='1';
require_once('../lib/init.php');

usage();

// grab list of files from table
$sql = "SELECT id, file, user,action FROM upload WHERE action != 'quarantine'";
$db_results = mysql_query($sql, dbh());

$files = array();
$files['add'] = array();
$files['delete'] = array();

while ($results = mysql_fetch_assoc($db_results)) {

	$action = $results['action'];

	$files[$action][] = $results;

} // end while

/* Make sure we have write access to the upload dir */
$upload_dir = conf('upload_dir');

if (!$upload_dir) { 
	echo "\nError: No Upload Directory Defined\n";
	exit;
}

if (!@is_writeable($upload_dir)) { 
	echo "\n" . _('Error: Unable to write to') .  " $upload_dir ". "\n";
	exit;
}

$catalog_id = find_upload_catalog($upload_dir);

if (!$catalog_id) { 
	cli_out(_("Error: Upload directory not inside a catalog"));
	exit;
} // we must have a valid upload dir

$catalog = new Catalog($catalog_id);

/* Itterate through the files we need to move */
foreach ($files['add'] as $data) { 

	/* Make sure that the target filename doesn't exist */
	$target_file = $upload_dir . "/" . basename($data['file']);

	if (file_exists($target_file)) { 
		echo 'Error: File Exists';
		debug_event('file_exists','Error: $target_file already exist','1');
		continue;
	}

	$results = copy($data['file'],$target_file);
	if (!$results) { echo "Error: Unable to copy " . $data['file'] . " skipping...\n"; continue; } 
	
	/* Check the filesize to make sure it copied */
	$new_sum = filesize($target_file);
	$old_sum = filesize($data['file']);

	if ($new_sum != $old_sum || !$new_sum) { 
		echo "Error: Size Inconsistency, not deleting " . $data['file'] . "\n";
		continue;
	}

	$results = unlink($data['file']);
	if (!$results) { echo "Error: Unable to delete " . $data['file'] . "\n";}
	
	else { 
		echo _('Adding') . " " . $target_file . " " . _('to database') . "\n";
		
		$catalog->add_file($target_file);
		
		/* Remove it from the catalog */
		$sql = "DELETE FROM upload WHERE id='" . $data['id'] . "'";
		$db_results = mysql_query($sql, dbh());
	}
	
} // end foreach 

/* Itterate through the files we need to delete */
foreach ($files['delete'] as $data) { 

	$results = unlink($data['file']);
	if (!$results) { echo "Error: Unable to Delete File\n"; } 
	else { 
		echo _('Deleted') . " " . $data['file'] . "\n"; 
		$sql = "DELETE FROM upload WHERE id='" . $data['id'] . "'";
		$db_results = mysql_query($sql, dbh());
	}

} // end foreach

exit();

/*!
	@function usage()
	@discussion echo the help for this script
*/

function usage( ) {
	$text = _( "
	
************* WARNING *************
This script will move, and 
potentially delete uploaded files.
************* WARNING *************

All files marked for add will be moved to the upload directory. All files 
marked for deletion will be deleted. This script must be run as a user with
sufficient rights to perform the above two functions. 

	\n" );
	echo $text;
	
	$text = _( "Continue?  (Y/N):" );
	echo $text;
	
	// grab a character ignoring whitespace
	do {
		$input= fgetc( STDIN );
	} while ( trim( $input ) == '' );

	if(strcasecmp($input,"y") != 0 ) {
		echo "\nExiting...\n";
		exit;
	}
}	// usage()

/*!
	@function cli_out()
	@discussion util for error formatting
	@param 	$text the message to be output
	@param	$mode to STDERR (0) or STDOUT (1, default)
*/

function cli_out( $text, $mode = 1 ) {
	switch( $mode ) {
		case 0:
			$dest = STDERR;
			$pre = _( "Error:  " );
			$post = _( "!\n" );
			break;
		case 1:
		default:
			$dest = STDOUT;
			$pre = "";
			$post = "\n";
	}
	fwrite( $dest, $pre . $text . $post );
}	// error_out



?>
