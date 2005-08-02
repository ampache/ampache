<?php
/*

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

/* This isn't working yet, don't let em try! */
exit();

$no_session='1';
require_once( '../modules/init.php' );
require_once( '../lib/upload.php' );
usage();

// check for correct db version
$sql = "SELECT value FROM update_info WHERE `key` = 'db_version'";
$db_results = mysql_query( $sql, dbh() );
if( mysql_num_rows( $db_results ) != 1 ) {
	$text = _( 'expected exactly 1 row in update_info table' );
	cli_out( $text, 0 );
	exit;
}
$results = mysql_fetch_object( $db_results );
$db_ver = $results->value;

if( $db_ver < $reqd_db_ver ) {
	$text = _( "this script requires database version $reqd_db_ver or higher" );
	cli_out( $text, 0 );
	exit;
}

// grab list of files from table
$sql = "SELECT id, file, user FROM upload WHERE action = 'quarantine'";
$db_results = mysql_query( $sql, dbh() );
while( $results = mysql_fetch_object( $db_results ) ) {
	$file = $results->file
	$dir = dirname( $file );
	$cat_id = dir_catalog( $dir );
	if( $cat_id != -1 ) {	// then this file is is a catalog hierarchy
		//check if it's been added to the catalog already
		$catalog = new Catalog( $cat_id );
		if( $catalog->check_local_mp3( $file ) ) {
			$text = $file . _( ' is already in a catalog' );
			cli_out( $text, 0 );
			continue;
		}
	}

	// getting ready to move	
	// check for each user's quar dir pref and source dir
	// can we write to both?
	$upload_user = new User( 0, $results->user );
	$quar_dir = $upload_user->prefs['quarantine_dir'];
	if( !is_writable( $quar_dir ) || !is_writable( $dir ) ) {
		$text = $file . _( ' cannot write to file directory or quarantine directory' );
		cli_out( $text, 0 );
		continue;
	}
//	move files
	$dest_file = $quar_dir . '/' . basename( $file );
	$file_move_ok = rename( $file, $dest_filename );
	if( !$file_move_ok ) {  
		$text = $file . _( ' could not move file to quarantine directory ' ) . $dest_filename;
		cli_out( $text, 0 );
		continue;
	} else {
		$text = _( 'Moved ' ) . $file . _( ' to ' ) . $dest_filename . ".\n";	
		cli_out( $text, 1 );
	}
	// update upload table
	$sql = "UPDATE upload SET file = $dest_filename WHERE id = $results->id";
	$db_results = mysql_query( $sql, dbh() );
}	// while there are quarantined entries in the upload table

exit;

/*!
	@function usage()
	@discussion echo the help for this script
*/

function usage( ) {
	$text = _( "
*** WARNING ***
This script will attempt to move your music files!  
*** WARNING ***

This script will process any pending quarantine files for the new uploads system.
You must be running update $reqd_db_ver or higher.  Your old upload directory and 
your new quarantine directory must be readable and writable by your webserver user.
It must be run from the ampache/bin directory.
	\n" );
	cli_out( $text, 1 );
	
	$text = _( "Continue?  (Y/N):\t" );
	cli_out( $text, 1 );
	
	// grab a character ignoring whitespace
	do {
		$input= fgetc( STDIN );
	} while ( trim( $input ) == '' );

	if( $input != 'Y' ) {
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
			$post = "";
	}
	fwrite( $dest, $pre . $text . $post );
}	// error_out



?>
