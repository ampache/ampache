<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION','1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';
$version = "1.0";

if (count($GLOBALS['argv']) != 2) {
    usage();
    exit;
} else {
    $filename = $GLOBALS['argv'][1];
}

if(!file_exists($filename)) {
    print T_("File not found.");
    print "\n";
    usage();
    exit;
}

printf(T_('Reading: %s'), $filename);
print "\n";

/* Attempt to figure out what catalog it comes from */
$sql = "SELECT `catalog`.`id` FROM `song` INNER JOIN `catalog` ON `song`.`catalog`=`catalog`.`id` WHERE `song`.`file` LIKE '%" . Dba::escape($filename) . "'";
$db_results = Dba::read($sql);
$results = Dba::fetch_assoc($db_results);

$catalog = Catalog::create_from_id($results['id']);

$dir_pattern = $catalog->sort_pattern;
$file_pattern = $catalog->rename_pattern;

$info = new vainfo($filename, array('music'), '', '', '', $dir_pattern, $file_pattern);
if(isset($dir_pattern) || isset($file_pattern)) {
    printf(T_('Using: %s AND %s for file pattern matching'), $dir_pattern, $file_pattern);
    print "\n";
}
$info->get_info();
$results = $info->tags;
$keys = vainfo::get_tag_type($results);
$ampache_results = vainfo::clean_tag_info($results, $keys, $filename);

echo "\n";
echo T_('Raw results:');
echo "\n\n";
print_r($info);
echo "\n------------------------------------------------------------------\n";
printf(T_('Final results seen by Ampache using %s:'), implode(' + ', $keys));
echo "\n\n";
print_r($ampache_results);

function usage() {
    global $version;

    $text  = sprintf(T_('%s Version %s'), 'print_tags.inc', $version);
    $text .= "\n\n";
    $text .= T_('Usage:');
    $text .= "\n";
    $text .= T_('php print_tags.inc <Filename>');
    $text .= "\n\n";

    return print $text;

}// usage()

?>
