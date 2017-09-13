<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
