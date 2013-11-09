<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

require_once 'lib/init.php';

UI::show_header();

/* Switch on the action to be performed */
switch ($_REQUEST['action']) {
    // Show a Users "Profile" page
    case 'show_user':
        $client = new User($_REQUEST['user_id']);
        require_once Config::get('prefix') . '/templates/show_user.inc.php';
    break;
    case 'user_stats':
        /* Get em! */
        $working_user = new User($_REQUEST['user_id']);

        /* Pull favs */
        $favorite_artists       = $working_user->get_favorites('artist');
        $favorite_albums        = $working_user->get_favorites('album');
        $favorite_songs         = $working_user->get_favorites('song');

        require_once Config::get('prefix') . '/templates/show_user_stats.inc.php';

    break;
    // Show stats
    case 'newest':
        require_once Config::get('prefix') . '/templates/show_newest.inc.php';
    break;
    case 'popular':
        require_once Config::get('prefix') . '/templates/show_popular.inc.php';
    break;
    case 'highest':
        require_once Config::get('prefix') . '/templates/show_highest.inc.php';
    break;
    case 'userflag':
        require_once Config::get('prefix') . '/templates/show_userflag.inc.php';
    break;
    case 'show':
    default:
        require_once Config::get('prefix') . '/templates/show_stats.inc.php';
    break;
} // end switch on action

UI::show_footer();

?>
