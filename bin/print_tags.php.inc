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
$no_session = '1';
require ("../lib/init.php");

/**
 * Set this to true if you want it to e-mail a report
 * to tags@ampache.org (usefull for troubleshooting
 */
//$send_mail = true;


$filename = usage();

echo "Reading: $filename\n";

$info = new vainfo($filename);
$info->get_info();
$results = $info->tags;
$results['file'] = $filename;
$key = get_tag_type($results);
$ampache_results = clean_tag_info($results,$key,$filename);

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

