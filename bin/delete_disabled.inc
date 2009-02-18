<?php
/*

 Copyright Ampache.org
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

/**
 * Delete Disabled
 * This WILL DELETE MUSIC!!!! it does what it's name would suggest and deletes
 * any disabled songs
 */

/* will not delete anything if debug is true */
$debug = true;


define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';

if ($debug) { echo "DEBUG ENABLED WILL NOT DELETE FILES!\n"; } 

/* Get a list of filenames */
$sql = "SELECT `id`,`file` FROM song WHERE enabled='0'"; 
$db_results = Dba::query($sql); 

while ($row = Dba::fetch_assoc($db_results)) { 
	if ($debug) { 
		echo "Would Delete: " . $row['file'] . "\n";
	}
	else { 
		echo "Deleting: " . $row['file'] . "\n"; 
		unlink($row['file']); 
		$sql = "DELETE FROM `song` WHERE `id`='" . Dba::escape($row['id']) . "'";
		$del_results = Dba::query($sql);
	} 
} // end while

?>
