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

/*
 * Use with caution, this hasn't been heavily tested!!!
 */

define('NO_SESSION','1');
require_once '../lib/init.php';

if (!$GLOBALS['argv']['1'] || $GLOBALS['argv']['1'] == '-h') { usage(); }
else {
    $dirname    = $GLOBALS['argv']['1'];
    $type        = $GLOBALS['argv']['2'];
}

// Make sure the output dir is valid and writeable
if (!is_writeable($dirname)) {
    printf (T_('Error: Directory %s not writeable'), $dirname);
    echo "\n"; 
}

// Switch on the type of playlist dump we want to do 
// here
switch ($type) {
    case 'playlists':

    break;
    case 'artist':

    break;
    default:

    break;
} // end type switch

/* FUNCTIONS */
function usage() {

$desc1 = T_("This will dump a collection of m3u playlists based on type");
$desc2 = T_("Types:");
$desc3 = T_("Dumps all Albums as individual playlists");
$desc4 = T_("Dumps all of your Playlists as m3u's");
$desc5 = T_("Dumps all Artists as individual playlists");

$string = "write_playlists.php.inc [-h] <DIRNAME> <TYPE>

    $desc1
    $desc2
        default        $desc3
        playlists    $desc4
        artist        $desc5\n\n";

    exit($string); 

} // useage
?>
