<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
 */

define('NO_SESSION','1');
define('CLI', 1);

$path = dirname(__FILE__);
$prefix = realpath($path . '/../');
require_once $prefix . '/lib/init.php';

ob_end_flush();

$catclean = 0;        //All off by default
$catverify = 0;
$catadd = 0;
$artadd = 0;
$plimp = 0;

if (count($_SERVER['argv']) == 1) { 
    $operations_string = "\n\t". T_('- All Catalog Operations');
} 

if (count($_SERVER['argv']) > 1) {
    for ($x = 1; $x < count($_SERVER['argv']); $x++) {

        if ($_SERVER['argv'][$x] == "-c") {
            $operations_string .= "\n\t" . T_('- Catalog Clean');
            $catclean = 1;
        } 
        elseif ($_SERVER['argv'][$x] == "-v") {
            $operations_string .= "\n\t" . T_('- Catalog Verify');
            $catverify = 1;
        } 
        elseif ($_SERVER['argv'][$x] == "-a") {
            $operations_string .= "\n\t" . T_('- Catalog Add');
            $catadd = 1;
        }
        elseif ($_SERVER['argv'][$x] == "-g") { 
            $operations_string .= "\n\t" . T_('- Catalog Art Gather');
            $artadd = 1; 
        } 
        elseif ($_SERVER['argv'][$x] == "-i") { 
            $operations_string .= "\n\t" . T_('- Playlist Import');
            $plimp = 1; 
        }
        else {
            if ($where) $where .= " OR ";
            $where .= "name LIKE '%" . Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $_SERVER['argv'][$x])) . "%'";
        }
    }
}

if (count($_SERVER['argv']) != 1 AND $artadd != 1 && $catclean != 1 && $catverify != 1 && $catadd != 1) {
        usage();
    exit; 
}

if ($artadd == 0 && $catclean == 0 && $catverify == 0 && $catadd == 0) {    //didn't pass any clean/verify/add arguments
    $catclean = 1;                        //set them all to on
    $catverify = 1;
    $catadd = 1;
    $artadd = 1;
}

echo T_("Starting Catalog Operations...") . $operations_string . "\n"; 

if ($where) $where = "($where) AND catalog_type='local'";
else $where = "catalog_type='local'";
$sql = "SELECT id FROM catalog";
if ($where) $sql .= " WHERE $where";
$db_results = Dba::read($sql);

ob_start("ob_html_strip",'1024',true);

while ($row = Dba::fetch_row($db_results)) {

    $catalog = Catalog::create_from_id($row['0']);
    printf(T_('Reading: %s'), $catalog->name);
    ob_flush(); 
    echo "\n"; 
    if ($catclean == 1) {
        // Clean out dead files
        echo T_("- Starting Clean - ");
        echo "\n";
        $catalog->clean_catalog();
        echo "------------------\n\n";
    }

    if ($catverify == 1) {
        // Verify Existing
        echo T_("- Starting Verify - ");
        echo "\n";
        $catalog->verify_catalog($row['0']);
        echo "-------------------\n\n";
    }

    if ($catadd == 1) {
        // Look for new files
        echo T_("- Starting Add - ");
        echo "\n";
        $options = array();
        if ($artadd == 1) {
            $options['gather_art'] = true;
        }
        if ($plimp == 1) {
            $options['parse_playlist'] = true;
        }
        $catalog->add_to_catalog($options);
        echo "----------------\n\n";
    } elseif ($artadd == 1) { 
        // Look for media art
        echo T_('Starting Media Art Search');
        echo "\n"; 
        $catalog->gather_art();
        echo "----------------\n\n";
    } 
}

Dba::optimize_tables();

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
    echo T_("- Catalog Update -");
    echo "\n";
    echo T_("Usage: catalog_update.inc [CATALOG NAME] [-c|-v|-a|-g|-t|-i]");
    echo "\n\t"; 
    echo T_("Default behavior is to do all except playlist import");
    echo "\n-c\t";
    echo T_('Clean Catalogs');
    echo "\n-v\t";
    echo T_('Verify Catalogs');
    echo "\n-a\t";
    echo T_('Add to Catalogs');
    echo "\n-i\t";
    echo T_('Import Playlists');
    echo "\n-g\t";
    echo T_('Gather Art');
    echo "\n";
    echo "----------------------------------------------------------";
    echo "\n";
}

?>
