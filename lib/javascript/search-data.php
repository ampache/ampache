<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation
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

require_once '../init.php';

function arrayToJSON($array)
{
    $json = '{ ';
    foreach ($array as $key => $value) {
        $json .= '"' . $key . '" : ';
        if (is_array($value)) {
            $json .= arrayToJSON($value);
        } else {
            // Make sure to strip backslashes and convert things to
            // entities in our output
            $json .= '"' . scrub_out(str_replace('\\', '', $value)) . '"';
        }
        $json .= ' , ';
    }
    $json = rtrim($json, ', ');
    return $json . ' }';
}

Header('content-type: application/x-javascript');

$search = new Search(null, $_REQUEST['type']);

echo 'var types = ';
echo arrayToJSON($search->types) . ";\n";
echo 'var basetypes = ';
echo arrayToJSON($search->basetypes) . ";\n";
echo 'removeIcon = \'<a href="javascript: void(0)">' . UI::get_icon('disable', T_('Remove')) . '</a>\';';
