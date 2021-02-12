<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

define('NO_SESSION', '1');
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../');
$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

ob_end_flush();

$tmpmemlimoff   = 0;        //All off by default
$catclean       = 0;
$catverify      = 0;
$catadd         = 0;
$artadd         = 0;
$infoupdate     = 0;
$plimp          = 0;
$optimizetables = 0;
$catmove        = 0;
$cattype        = false;
$catname        = false;
$catpath        = '';
$options        = getopt("hn:t:mvcaguiop:");

if (count($options) == 0) {
    $operations_string = "\n\t- " . T_('Doing all Catalog operations');
} else {
    $operations_string = '';
    if (array_key_exists('h', $options)) {
        usage();

        return false;
    }
    if (array_key_exists('m', $options)) {
        $operations_string .= "\n\t- " . T_('Temporarily deactivating PHP memory limit');
        $tmpmemlimoff = 1;
    }
    if (array_key_exists('n', $options)) {
        $operations_string .= "\n\t- " . T_('Setting Catalog name');
        $catname = Dba::escape(preg_replace("/[^a-z0-9\. -]/i", "", $options['n']));
    }
    if (array_key_exists('t', $options)) {
        $operations_string .= "\n\t- " . T_('Setting Catalog type');
        $cattype = strtolower($options['t']);
    }
    if (array_key_exists('c', $options)) {
        $operations_string .= "\n\t- " . T_('Cleaning Catalog/s');
        $catclean = 1;
    }
    if (array_key_exists('v', $options)) {
        $operations_string .= "\n\t- " . T_('Verifying Catalog/s');
        $catverify = 1;
    }
    if (array_key_exists('a', $options)) {
        $operations_string .= "\n\t- " . T_('Adding new media to Catalog/s');
        $catadd = 1;
    }
    if (array_key_exists('g', $options)) {
        $operations_string .= "\n\t- " . T_('Gathering new media art');
        $artadd = 1;
    }
    if (array_key_exists('u', $options)) {
        $operations_string .= "\n\t- " . T_('Update artist information and fetch similar artists from last.fm');
        $infoupdate = 1;
    }
    if (array_key_exists('i', $options)) {
        $operations_string .= "\n\t- " . T_('Importing playlist/s');
        $plimp = 1;
    }
    if (array_key_exists('o', $options)) {
        $operations_string .= "\n\t- " . T_('Database table optimization');
        $optimizetables = 1;
    }
    if (array_key_exists('m', $options)) {
        $operations_string .= "\n\t- " . T_('Temporarily deactivating PHP memory limit');
        $tmpmemlimoff = 1;
    }
    if (array_key_exists('p', $options)) {
        $operations_string .= "\n\t- " . T_('Moving Catalog path');
        $catmove = 1;
        $catpath = Dba::escape(preg_replace("/\//i", "/", $options['p']));
    }
}
if (count($_SERVER['argv']) != 1 && $artadd != 1 && $infoupdate != 1 && $catclean != 1 && $catverify != 1 && $catadd != 1 && $plimp != 1 && $catmove != 1) {
    usage();

    return false;
}

//didn't pass any clean/verify/add arguments
if ($catclean == 0 && $catverify == 0 && $catadd == 0 && $artadd == 0 && $optimizetables == 0 && $plimp == 0 && $infoupdate != 1 && $catmove != 1) {
    $catclean       = 1;     //set them all to on
    $catverify      = 1;
    $catadd         = 1;
    $artadd         = 1;
    $optimizetables = 1;
}
// if moving, don't do anything else
if ($catname && $catmove) {
    $catclean       = 0;
    $catverify      = 0;
    $catadd         = 0;
    $artadd         = 0;
    $optimizetables = 0;
}
echo "\n----------------------------------------------------------\n";
echo T_("Starting Catalog operations...") . $operations_string . "\n";
echo "----------------------------------------------------------\n\n";

// -------- Options before the Catalog actions loop
if ($tmpmemlimoff == 1) {
    // Temporarily deactivate PHP memory limit
    echo "\033[31m- " . T_("Deactivated PHP memory limit") . " -\033[0m\n";
    ini_set("memory_limit", "-1");
    echo "------------------\n\n";
}

//$options = array(); // for $catadd
if ($artadd == 1) {
    $options["gather_art"] = true;
} else {
    $options["gather_art"] = false;
}
if ($plimp == 1) {
    $options["parse_playlist"] = true;
} else {
    $options["parse_playlist"] = false;
}
// --------
$where = "";
if ($catname !== false) {
    $where = " name = '$catname' ";
}
if ($cattype !== false) {
    $where .= "catalog_type='$cattype'";
} elseif (!$where) {
    $where .= "catalog_type='local'";
}
$sql = "SELECT `id` FROM `catalog`";
if ($where) {
    $sql .= " WHERE $where";
}
$db_results = Dba::read($sql);

ob_start("ob_html_strip", "1024", true);

while ($row = Dba::fetch_assoc($db_results)) {
    $catalog = Catalog::create_from_id($row["id"]);
    /* HINT: Catalog Name */
    printf(T_("Reading Catalog: '%s'"), $catalog->name);
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
        echo "- " . T_("Start verifying media related to Catalog entries") . " - \n";
        echo "\n";
        $catalog->verify_catalog();
        echo "-------------------\n\n";
    }
    if ($catadd == 1) {
        // Look for new files
        echo "- " . T_("Start adding new media") . " - \n";
        echo "\n";
        $catalog->add_to_catalog($options);
        Album::update_album_artist();
        echo "----------------\n\n";
    } elseif ($artadd == 1) {
        // Look for media art
        echo "- " . T_("Start searching new media art") . " - \n";
        echo "\n";
        $catalog->gather_art();
        echo "----------------\n\n";
    }
    if ($infoupdate == 1) {
        // Look for updated artist information. (missing or < 6 months since last update)
        echo "- " . T_("Update artist information and fetch similar artists from last.fm") . " - \n";
        echo "\n";
        $artist_info = $catalog->get_artist_ids("info");
        $catalog->gather_artist_info($artist_info);
        echo "----------------\n\n";
    }
    if ($catmove == 1) {
        // Migrate a catalog from the current path to a new one.
        echo "- " . T_("Moving Catalog path") . " - \n";
        echo "\n" . $catalog->path . " -> " . $catpath . "\n\n";
        if (is_dir($catpath) && isset($catalog->path)) {
            if ($catalog->move_catalog_proc($catpath)) {
                echo "- " . T_("The Catalog path has changed") . " - \n";
            } else {
                echo "- " . T_("There Was a Problem") . " - \n";
            }
        } else {
            echo "- " . T_("There is an error with your parameters") . " - \n";
        }
        echo "----------------\n";
    }
}
if ($catclean == 1 || $catverify == 1) {
    Catalog::garbage_collection();
}
if ($optimizetables == 1) {
    // Optimize Database Tables
    echo "- " . T_("Optimizing database tables") . " - \n";
    echo "\n";
    Dba::optimize_tables();
    echo "------------------\n\n";
}

ob_end_flush();
echo "\n\n";

/**
 * ob_html_strip
 * @param string $string
 * @return string|string[]|null
 */
function ob_html_strip($string)
{
    $string = str_replace("<br />", "\n", $string);
    $string = strip_tags($string);
    $string = html_entity_decode($string);
    $string = preg_replace("/[\r\n]+[\s\t]*[\r\n]+/","\n",$string);
    $string = trim($string);

    return $string;
} // ob_html_strip

function usage()
{
    echo "\n";
    echo "----------------------------------------------------------\n\t\t";
    echo T_("Usage:") . " \n\033[32m";
    echo T_("catalog_update.inc [-n CATALOG NAME] [-t CATALOG TYPE] [-h|-m|-c|-v|-a|-g|-u|-i|-o|-p]") . "\033[0m (\033[31m!\033[0m)" . " \033[0m\n";
    echo "----------------------------------------------------------\n";
    echo T_("Default behavior is to do all except temporarily deactivate the php memory limit") . " \n";
    echo "----------------------------------------------------------";
    echo "\n-h\t" . T_('This help message');
    echo "\n-n\t" . T_('Name of Catalog (optional)');
    echo "\n-t\t" . T_('Type of Catalog (optional)');
    echo "\n-m\t" . T_('Temporarily deactivates PHP memory limit') . " (\033[31m1\033[0m)";
    echo "\n-c\t" . T_('Cleans Catalogs from orphaned entries');
    echo "\n-v\t" . T_('Verifies Catalog entries and updates them if related files have new information');
    echo "\n-a\t" . T_('Adds new media to Catalogs');
    echo "\n-g\t" . T_('Gathers media Art');
    echo "\n-u\t" . T_('Update artist information and fetch similar artists from last.fm') . " " . T_('Make sure lastfm_api_key is set in your configuration file');
    echo "\n-i\t" . T_('Imports playlists');
    echo "\n-o\t" . T_('Optimizes database tables') . " (\033[31m2\033[0m)" . " \n";
    echo "\n-p\t" . T_('Change the path of an existing Catalog') . " " . T_('Requires a Catalog name') . " \n";
    echo "----------------------------------------------------------\033[31m\n";
    echo "1. " . T_('Use this option at your own risk! Your system could crash or become unresponsive due to huge memory consumption!') . "\n";
    echo "2. " . T_("Depending on your system's performance, this option may need a long time to finish and could greatly slow down other database processes if you have large Catalogs!") . "\n";
    echo "!  " . T_('The switches [-m|-o] can only be used, if one of the other switches [-c|-v|-a|-g] is used') . " \033[0m\n";
    echo "----------------------------------------------------------\n";
}
