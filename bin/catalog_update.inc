<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
define('CLI', 1);

$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

ob_end_flush();

$tmpmemlimoff = 0;        //All off by default
$catclean = 0;
$catverify = 0;
$catadd = 0;
$artadd = 0;
$plimp = 0;
$optimizetables = 0;

if (count($_SERVER['argv']) == 1) { 
    $operations_string = "\n\t- ". T_('Doing all catalog operations');
} 

if (count($_SERVER['argv']) > 1) {
    for ($x = 1; $x < count($_SERVER['argv']); $x++) {

        if ($_SERVER['argv'][$x] == "-m") {
            $operations_string .= "\n\t- " . T_('Temporary deactivate PHP memory limit');
            $tmpmemlimoff = 1;
        }
        elseif ($_SERVER['argv'][$x] == "-c") {
            $operations_string .= "\n\t- " . T_('Cleaning catalog/s');
            $catclean = 1;
        } 
        elseif ($_SERVER['argv'][$x] == "-v") {
            $operations_string .= "\n\t- " . T_('Verifying catalog/s');
            $catverify = 1;
        } 
        elseif ($_SERVER['argv'][$x] == "-a") {
            $operations_string .= "\n\t- " . T_('Adding new media to catalog/s');
            $catadd = 1;
        }
        elseif ($_SERVER['argv'][$x] == "-g") { 
            $operations_string .= "\n\t- " . T_('Gathering new media art');
            $artadd = 1;
        } 
        elseif ($_SERVER['argv'][$x] == "-i") { 
            $operations_string .= "\n\t- " . T_('Importing playlist/s');
            $plimp = 1;
        }
        elseif ($_SERVER['argv'][$x] == "-o") { 
            $operations_string .= "\n\t- " . T_('Database table optimization');
            $optimizetables = 1;
        }
        else {
            if ($where) $where .= " OR ";
            $where .= "name = '" . Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x])) . "'";
        }
    }
}

if (count($_SERVER['argv']) != 1 AND $artadd != 1 && $catclean != 1 && $catverify != 1 && $catadd != 1) {
    usage();
    exit;
}

if ($catclean == 0 && $catverify == 0 && $catadd == 0 && $artadd == 0 && $optimizetables == 0) {    //didn't pass any clean/verify/add arguments
    $catclean = 1;     //set them all to on
    $catverify = 1;
    $catadd = 1;
    $artadd = 1;
    $optimizetables = 1;
}
echo "\n----------------------------------------------------------\n";
echo T_("Starting catalog operations...") . $operations_string . "\n";
echo "----------------------------------------------------------\n\n";

// -------- Options before the catalog actions loop
if ($tmpmemlimoff == 1) {
    // Temporarily deactivate PHP memory limit
    echo "\033[31m- " . T_("Deactivated PHP memory limit") . " -\033[0m\n";
    ini_set('memory_limit','-1');
    echo "------------------\n\n";
}

$options = array(); // for $catadd
if ($artadd == 1) {
    echo "- " . T_("Gathering art") . " - \n";
    $options['gather_art'] = true;
    } else {
    $options['gather_art'] = false;
}
if ($plimp == 1) {
    echo "- " . T_("Parsing playlists") . " - \n";
    $options['parse_playlist'] = true;
    } else {
    $options['parse_playlist'] = false;
}
// --------
if ($where) $where = "($where) AND catalog_type='local'";
else $where = "catalog_type='local'";
$sql = "SELECT id FROM catalog";
if ($where) $sql .= " WHERE $where";
$db_results = Dba::read($sql);

ob_start("ob_html_strip",'1024',true);

while ($row = Dba::fetch_row($db_results)) {

    $catalog = Catalog::create_from_id($row['0']);
    printf(T_('Reading catalog: %s'), $catalog->name);
    ob_flush();
    echo "\n";

    if ($catclean == 1) {
        // Clean out dead files
        echo "- " . T_("Start cleaning orphaned media entries") . " - \n";
        echo "\n";
        $catalog->clean_catalog();
        echo "------------------\n\n";
    }
    if ($catverify == 1) {
        // Verify Existing
        echo "- " . T_("Start verifying media related to catalog entries") . " - \n";
        echo "\n";
        $catalog->verify_catalog($row['0']);
        echo "-------------------\n\n";
    }
    if ($catadd == 1) {
        // Look for new files
        echo "- " . T_("Start adding new media") . " - \n";
        echo "\n";
        $catalog->add_to_catalog($options);
        echo "----------------\n\n";
    }
    elseif ($artadd == 1) { 
        // Look for media art
        echo "- " . T_('Start searching new media art') . " - \n";
        echo "\n";
        $catalog->gather_art();
        echo "----------------\n\n";
    }
}
    if ($optimizetables == 1) {
        // Optimize Database Tables
        echo "- " . T_('Optimizing database tables') . " - \n";
        echo "\n";
        Dba::optimize_tables();
        echo "------------------\n\n";
    }

ob_end_flush();
echo "\n";

function ob_html_strip($string) {

    //$string = preg_replace("/update_txt\('.+'\);update_txt\('(.+)','.+'\);/","$1",$string);
    //$string = preg_replace("/update_.+/","",$string);
    $string = str_replace('<br />', "\n", $string);
    $string = strip_tags($string);
    $string = html_entity_decode($string);
    $string = preg_replace("/[\r\n]+[\s\t]*[\r\n]+/","\n",$string);
    $string = trim($string);
    return $string;

} // ob_html_strip

function usage() {
    echo "\n";
    echo "----------------------------------------------------------\n\t\t";
    echo T_("- Catalog Update Help -");
    echo "\n\033[32m";
    echo T_("Usage: catalog_update.inc [CATALOG NAME] [-m|-c|-v|-a|-g|-i|-o]") . "\033[0m (\033[31m!\033[0m)";
    echo "\033[0m\n";
    echo "----------------------------------------------------------";
    echo "\n";
    echo T_("Default behavior is to do all except temporarily deactivate the php memory limit");
    echo "\n";
    echo "----------------------------------------------------------";
    echo "\n-m\t";
    echo T_('Temporarily deactivates PHP memory limit.') . " (\033[31m1\033[0m)";
    echo "\n-c\t";
    echo T_('Cleans catalogs from orphaned entries.');
    echo "\n-v\t";
    echo T_('Verifies catalog entries and updates them if related files have new information.');
    echo "\n-a\t";
    echo T_('Adds new media to catalogs.');
    echo "\n-g\t";
    echo T_('Gathers media Art.');
    echo "\n-i\t";
    echo T_('Imports playlists.');
    echo "\n-o\t";
    echo T_('Optimizes Database Tables.') . " (\033[31m2\033[0m)";
    echo "\n";
    echo "----------------------------------------------------------\033[31m\n";
    echo "1. " . T_('Use this option at your own risk! Your system could crash or become unresponsive due to huge memory consumption!') . "\n";
    echo "2. " . T_('Depending on your systems performance, this option could need a long time to finish and extremely slow down other database processes if you have big catalogs!') . "\n";
    echo "!  " . T_('The switches [-m|-i|-o] can only be used, if one of the other switches [-c|-v|-a|-g] is used.');
    echo "\033[0m\n";
    echo "----------------------------------------------------------";
    echo "\n";
}

?>
