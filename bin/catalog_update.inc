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

echo "- Catalog Update -\n Useage: catalog_update.inc [CATALOG NAME] [-c|-v|-a]\n-c\tClean Catalogs\n-v\tVerify Catalogs\n-a\tAdd to Catalogs\n*Default behavior is to do all 3*\n"; 
ob_end_flush(); 

$catclean = 0;		//All off by default
$catverify = 0;
$catadd = 0;

if (count($_SERVER['argv']) > 1) {
	for ($x = 1; $x < count($_SERVER['argv']); $x++) {

		if ($_SERVER['argv'][$x] == "-c") {
			$catclean = 1;
		} else if ($_SERVER['argv'][$x] == "-v") {
			$catverify = 1;
		} else if ($_SERVER['argv'][$x] == "-a") {
			$catadd = 1;
		} else {
			if ($where) $where .= " OR ";
			$where .= "name LIKE '%" . Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x])) . "%'";
		}
	}
}

if ($catclean == 0 && $catverify == 0 && $catadd == 0) {	//didn't pass any clean/verify/add arguments
	$catclean = 1;						//set them all to on
	$catverify = 1;
	$catadd = 1;
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
	if ($catclean == 1) {
		// Clean out dead files
		echo "- Starting Clean - \n";   
		$catalog->clean_catalog();
		echo "------------------\n\n";
	}

	if ($catverify == 1) {
		// Verify Existing
		echo "- Starting Verify - \n"; 
		$catalog->verify_catalog($row['0']);
		echo "-------------------\n\n";
	}
 
	if ($catadd == 1) {
		// Look for new files
		echo "- Starting Add - \n"; 
		$catalog->add_to_catalog();
		echo "----------------\n\n";
	}

} // end foreach 

function ob_html_strip($string) { 

	$string = preg_replace("/update_txt\('.+'\);update_txt\('(.+)','.+'\);/","$1",$string);
	$string = preg_replace("/update_.+/","",$string); 
	$string = strip_tags($string); 
	return $string; 

} // ob_html_strip

?>
