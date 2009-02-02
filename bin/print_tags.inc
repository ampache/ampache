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

if (count($GLOBALS['argv']) == '1') { $filename = usage(); }
else { 
	$filename = $GLOBALS['argv']['1']; 
	$send_mail = $GLOBALS['argv']['2'];
}

echo "Reading: $filename\n";

/* Attempt to figure out what catalog it comes from */
$sql = "SELECT `catalog`.`id` FROM `song` INNER JOIN `catalog` ON `song`.`catalog`=`catalog`.`id` WHERE `song`.`file` LIKE '%" . Dba::escape($filename) . "'";
$db_results = Dba::query($sql);
$results = Dba::fetch_assoc($db_results);

$catalog = new Catalog($results['id']);

$info = new vainfo($filename,'','','',$catalog->sort_pattern,$catalog->rename_pattern);
echo "Using: $catalog->sort_pattern AND $catalog->rename_pattern for file pattern matching\n";
$info->get_info();
$results = $info->tags;
$results['file'] = $filename;
$key = vainfo::get_tag_type($results);
$ampache_results = vainfo::clean_tag_info($results,$key,$filename);

if ($send_mail) { 
	$getid3_results = print_r($info,1);
	$amp_results = print_r($ampache_results,1);
	$body = $getid3_results . "\n-----\n\n" . $amp_results;
	$subject = "[Ampache] Print Tags - $filename";
	mail('tags@ampache.org',$subject,$body);
}


echo "Results Seen by GetID3()\n";
print_r($info);
echo "\n------------------------------------------------------------------\n";
echo "Results Seen by Ampache using $key\n";
print_r($ampache_results);

function usage( ) {
        $text = _( "[print_tags.php.inc]
This commandline script will display the tag information for the specified filename as it will 
appear to Ampache. 
        \n" );
        echo $text;
        
        $text = _( "Filename:" );
        echo $text;
        
        // grab a character ignoring whitespace
	$input = trim(fgets(STDIN));

	return $input;

}       // usage()

?>

