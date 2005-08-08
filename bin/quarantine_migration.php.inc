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

$no_session='1';
require_once('../modules/init.php');

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

if (!@is_writeable($upload_dir)) { 
	cli_out("\n" . _("Error: Unable to write to") .  $upload_dir . " \n");
}


/* Itterate through the files we need to move */
$adds = &$files['add'];
foreach ($adds as $data) { 

	/* Make sure that the target filename doesn't exist */

	$command = "mv " . $data['file'] . " " . $upload_dir;
	$command = escapeshellcmd($command);

	if (exec($command)) { 
		cli_out("\n" . _("Moved") . " " . $data['file'] . "\n");
		$sql = "DELETE FROM upload WHERE id='" . $data['id'] . "'";
		$db_results = mysql_query($sql, dbh());
	}
	else { 
		cli_out("\n" . _("Move Failed") . " " . $data['file'] . "\n");
	}

} // end foreach 

exit();

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

exit;

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
