<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Catalog Update
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

define('NO_SESSION','1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';


ob_end_flush();

$catclean = 0;		//All off by default
$catverify = 0;
$catadd = 0;
$thumbadd =0; 
$artadd = 0; 

if (count($_SERVER['argv']) == 1) { 
	$operations_string = "\n\t". _('- All Catalog Operations');
} 

if (count($_SERVER['argv']) > 1) {
	for ($x = 1; $x < count($_SERVER['argv']); $x++) {

		if ($_SERVER['argv'][$x] == "-c") {
			$operations_string .= "\n\t"._('- Catalog Clean');
			$catclean = 1;
		} 
		elseif ($_SERVER['argv'][$x] == "-v") {
			$operations_string .= "\n\t"._('- Catalog Verify');
			$catverify = 1;
		} 
		elseif ($_SERVER['argv'][$x] == "-a") {
			$operations_string .= "\n\t"._('- Catalog Add');
			$catadd = 1;
		}
		elseif ($_SERVER['argv'][$x] == "-g") { 
			$operations_string .= "\n\t"._('- Catalog Art Gather');
			$artadd = 1; 
		} 
		elseif ($_SERVER['argv'][$x] == '-t') { 
			$operations_string .= "\n\t"._('- Generate Thumbnails');
			$thumbadd = 1; 
		} 
		else {
			if ($where) $where .= " OR ";
			$where .= "name LIKE '%" . Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x])) . "%'";
		}
	}
}

if (count($_SERVER['argv']) != 1 AND $artadd != 1 && $catclean != 1 && $catverify != 1 && $catadd != 1 && $thumbadd != 1) {
        usage();
	exit; 
}

if ($thumbadd == 0 && $artadd == 0 && $catclean == 0 && $catverify == 0 && $catadd == 0) {	//didn't pass any clean/verify/add arguments
	$catclean = 1;						//set them all to on
	$catverify = 1;
	$catadd = 1;
	$artadd = 1; 
	$thumbadd = 1; 
}

echo _("Starting Catalog Operations...") . $operations_string . "\n"; 

if ($where) $where = "($where) AND catalog_type='local'";
else $where = "catalog_type='local'";
$sql = "SELECT id FROM catalog";
if ($where) $sql .= " WHERE $where";
$db_results = Dba::read($sql);

ob_start("ob_html_strip",'1024',true);

while ($row = Dba::fetch_row($db_results)) {

	$catalog = new Catalog($row['0']);
	printf(_('Reading: %s'), $catalog->name);
	ob_flush(); 
	echo "\n"; 
	if ($catclean == 1) {
		// Clean out dead files
		echo _("- Starting Clean - ");
		echo "\n";
		$catalog->clean_catalog();
		echo "------------------\n\n";
	}

	if ($catverify == 1) {
		// Verify Existing
		echo _("- Starting Verify - ");
		echo "\n";
		$catalog->verify_catalog($row['0']);
		echo "-------------------\n\n";
	}

	if ($catadd == 1) {
		// Look for new files
		echo _("- Starting Add - ");
		echo "\n";
		$catalog->add_to_catalog();
		echo "----------------\n\n";
	}

	if ($artadd == 1) { 
		// Look for album art
		echo _('Starting Album Art Search');
		echo "\n"; 
		$catalog->get_art('',1);
		echo "----------------\n\n";
	} 

	if ($thumbadd == 1) { 
		// Generate the thumbnails
		echo _('Generating Thumbnails'); 
		echo "\n"; 
		$catalog->generate_thumbnails(); 
		echo "----------------\n\n";
	}

} // end foreach

Catalog::optimize_tables();

ob_end_flush(); 
echo "\n"; 

function ob_html_strip($string) {

	$string = preg_replace("/update_txt\('.+'\);update_txt\('(.+)','.+'\);/","$1",$string);
	$string = preg_replace("/update_.+/","",$string);
	$string = strip_tags($string);
	$string = preg_replace("/[\r\n]+[\s\t]*[\r\n]+/","\n",$string);
	$string = trim($string);
	return $string;

} // ob_html_strip

function usage() {
	echo _("- Catalog Update -");
	echo "\n";
	echo _("Usage: catalog_update.inc [CATALOG NAME] [-c|-v|-a|-g|-t]");
	echo "\n\t"; 
	echo _("Default behavior is to do all");
	echo "\n-c\t";
	echo _('Clean Catalogs');
	echo "\n-v\t";
	echo _('Verify Catalogs');
	echo "\n-a\t";
	echo _('Add to Catalogs');
	echo "\n-g\t";
	echo _('Gather Art');
	echo "\n-t\t"; 
	echo _('Generate Thumbnails');
	echo "\n";
	echo "----------------------------------------------------------";
	echo "\n";
}

?>
