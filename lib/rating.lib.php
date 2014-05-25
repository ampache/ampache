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

/**
 * show_rating
 * This takes an artist id and includes the right file
 */
function show_rating($object_id,$type)
{
    $rating = new Rating($object_id,$type);

    require AmpConfig::get('prefix') . '/templates/show_object_rating.inc.php';

} // show_rating

/**
 * get_rating_name
 * This takes a score and returns the name that we should use
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
