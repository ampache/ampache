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

define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';


echo "- Catalog Update -\n Useage: catalog_update.inc %catalog%\nStarting Catalog Clean, Verify and Add...\n\n";
ob_end_flush(); 

if (count($_SERVER['argv']) > 1) {
       for ($x = 1; $x < count($_SERVER['argv']); $x++) {
               if ($where) $where .= " OR ";
               $where .= "name LIKE '%" . Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x])) . "%'";
       }
}
if ($where) $where = "($where) AND catalog_type='local'";
else $where = "catalog_type='local'";
$sql = "SELECT id FROM catalog";
if ($where) $sql .= " WHERE $where";
$db_results = Dba::query($sql);

ob_start("ob_html_strip",'4096',true); 

while ($row = Dba::fetch_row($db_results)) { 

	$catalog = new Catalog($row['0']);
	echo "Reading: $catalog->name \n"; 
	echo "- Starting Clean -"; 	
	// Clean out dead files
	$catalog->clean_catalog();

	echo "- Starting Verify -"; 
	// Verify Existing
	$catalog->verify_catalog($row['0']);

	echo "- Starting Add - \n"; 
	// Look for new files
	$catalog->add_to_catalog();

} // end foreach 

function ob_html_strip($string) { 

	$string = preg_replace("/update_txt\('.+'\);update_txt\('(.+)','.+'\);/","$1",$string);
	$string = preg_replace("/update_.+/","",$string); 
	$string = strip_tags($string); 
	return $string; 

} // ob_html_strip

?>
