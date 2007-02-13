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

*/

define('NO_SESSION','1');
require ('../lib/init.php');

echo "[catalog_update.php.inc]\nStarting Catalog Clean/Update And Add\n\n";

if (count($_SERVER['argv']) > 1) {
       for ($x = 1; $x < count($_SERVER['argv']); $x++) {
               if ($where) $where .= " OR ";
               $where .= "name LIKE '%" . preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x]) . "%'";
       }
}
if ($where) $where = "($where) AND catalog_type='local'";
else $where = "catalog_type='local'";
$sql = "SELECT id FROM catalog";
if ($where) $sql .= " WHERE $where";
$db_results = mysql_query($sql, dbh());

while ($r = mysql_fetch_row($db_results)) { 

	$catalog = new Catalog($r[0]);
	
	// Clean out dead files
	$catalog->clean_catalog(0,0);

	$catalog->count = 0;

	// Verify Existing
	$catalog->verify_catalog(0,0,0);

	$catalog->count = 0;
	
	echo "\n\n";

	// Look for new files
	$catalog->add_to_catalog();
	echo "\n\n";
}


?>
