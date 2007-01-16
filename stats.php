<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*

 Show us the stats for the server and this user

*/
require_once('lib/init.php');

show_template('header');

$action = scrub_in($_REQUEST['action']); 

/* Switch on the action to be performed */
switch ($action) { 
	case 'user_stats':
		/* Get em! */
		$working_user = new User($_REQUEST['user_id']); 

                /* Pull favs */
                $favorite_artists       = $working_user->get_favorites('artist');
                $favorite_albums        = $working_user->get_favorites('album');
                $favorite_songs         = $working_user->get_favorites('song');

                require_once(conf('prefix') . '/templates/show_user_stats.inc.php');
	
	break;
	/* Show their stats */
	default: 
		/* Here's looking at you kid! */
		$working_user = $GLOBALS['user'];

		/* Pull favs */
		$favorite_artists	= $working_user->get_favorites('artist');
		$favorite_albums	= $working_user->get_favorites('album');
		$favorite_songs		= $working_user->get_favorites('song');

		require_once(conf('prefix') . '/templates/show_user_stats.inc.php');

		// Onlu do this is ratings are on 
		if (conf('ratings')) { 
			/* Build Recommendations from Ratings */
			$recommended_artists	= $working_user->get_recommendations('artist');
			$recommended_albums	= $working_user->get_recommendations('albums');
			$recommended_songs	= $working_user->get_recommendations('song');
	
			require_once(conf('prefix') . '/templates/show_user_recommendations.inc.php');
		} // if ratings on 

                show_box_top();
                /* Show Most Popular artist/album/songs */
                show_all_popular();

                /* Show Recent Additions */
                show_all_recent();
                show_box_bottom();
		
	break;
} // end switch on action

show_footer(); 

?>
