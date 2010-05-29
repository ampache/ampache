<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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
$version = "1.0";

$exts = Config::get('catalog_file_pattern');

$allowexts = preg_split('/\|/', $exts);

if (count($GLOBALS['argv']) == '1') {
	usage();
	exit;
} else {
	$filename = $GLOBALS['argv']['1'];
	$send_mail = $GLOBALS['argv']['2'];
}

if(!file_exists($filename)) {
	print _("File not found.");
	print "\n";
	usage();
	exit;
}

if(file_check($filename, $allowexts) === false) {
	print _("File type is not allowd.");
	print "\n";
	usage();
	exit;
}

printf(_('Reading: %s'), $filename);
print "\n";

/* Attempt to figure out what catalog it comes from */
$sql = "SELECT `catalog`.`id` FROM `song` INNER JOIN `catalog` ON `song`.`catalog`=`catalog`.`id` WHERE `song`.`file` LIKE '%" . Dba::escape($filename) . "'";
$db_results = Dba::read($sql);
$results = Dba::fetch_assoc($db_results);

$catalog = new Catalog($results['id']);

$info = new vainfo($filename,'','','',$catalog->sort_pattern,$catalog->rename_pattern);
if(isset($catalog->sort_pattern) AND isset($catalog->rename_pattern)) {
	printf(_('Using: %s AND %s for file pattern matching'), $catalog->sort_pattern, $catalog->rename_pattern);
	print "\n";
}
$info->get_info();
$results = $info->tags;
$key = vainfo::get_tag_type($results);
$ampache_results = vainfo::clean_tag_info($results,$key,$filename);

if ($send_mail) {
	$getid3_results = print_r($info,1);
	$amp_results = print_r($ampache_results,1);
	$body = $getid3_results . "\n-----\n\n" . $amp_results;
	$subject = "[Ampache] Print Tags - $filename";
	mail('tags@ampache.org',$subject,$body);
}


echo _("Results Seen by GetID3()");
echo "\n";
print_r($info);
echo "\n------------------------------------------------------------------\n";
printf(_('Results Seen by Ampache using %s'), $key[0]);
echo "\n";
print_r($ampache_results);

function usage() {
	global $version;

	$text  = sprintf(_('%s Version %s'), 'print_tags.inc', $version);
	$text .= "\n\n";
	$text .= _('Usage:');
	$text .= "\n";
	$text .= _('php print_tags.inc <Filename>');
	$text .= "\n\n";

	return print $text;

}// usage()

function file_check($filename, $allowexts) {

	$pathinfo = pathinfo($filename);
	$exts = $pathinfo['extension'];

	if(in_array($exts, $allowexts)) {
		return true;
	} else {
		return false;
	}
} // file_check()

?>
