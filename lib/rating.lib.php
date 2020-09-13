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

/**
 * show_rating
 * This takes an artist id and includes the right file
 * @param integer $object_id
 * @param string $type
 */
function show_rating($object_id, $type)
{
    $rating = new Rating($object_id, $type);

    require AmpConfig::get('prefix') . UI::find_template('show_object_rating.inc.php');
} // show_rating

/**
 * get_rating_name
 * This takes a score and returns the name that we should use
 * @param string $score
 * @return string
 */
function get_rating_name($score)
{
    switch ($score) {
        case '0':
            return T_("Don't Play");
        case '1':
            return T_("It's Pretty Bad");
        case '2':
            return T_("It's Ok");
        case '3':
            return T_("It's Pretty Good");
        case '4':
            return T_("I Love It!");
        case '5':
            return T_("It's Insane");
        // I'm fired
        default:
            return T_("Off the Charts!");
    } // end switch
} // get_rating_name
