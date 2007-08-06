<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
require_once 'lib/init.php';

show_header(); 

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
	//FIXME:: The logic in here should be moved to our metadata class
	case 'recommend_similar':
		// For now this is just MyStrands so verify they've filled out stuff
		if (!$GLOBALS['user']->has_access('25') || !$GLOBALS['user']->prefs['mystrands_pass'] || !$GLOBALS['user']->prefs['mystrands_user']) { 
			access_denied(); 
			exit; 
		} 

		// We're good attempt to dial up MyStrands 
		OpenStrands::set_auth_token(Config::get('mystrands_developer_key'));  
		$openstrands = new OpenStrands($GLOBALS['user']->prefs['mystrands_user'],$GLOBALS['user']->prefs['mystrands_pass']); 	

		if (!$openstrands) { 
			debug_event('openstrands','Unable to authenticate MyStrands user, or authtoken invalid','3'); 
			Error::add('general','Unable to authenticate MyStrands user, or authtoken invalid'); 
		} 

		// Do our recommendation
		switch ($_REQUEST['type']) { 
			case 'artist': 
				$artist = new Artist($_REQUEST['id']); 
				$seed = array('name'=>array($artist->name)); 
				$results = $openstrands->recommend_artists($seed); 
			break;
		} // end switch 

		// Run through what we've found and build out the data
		foreach ($results as $result) { 

			switch ($_REQUEST['type']) { 
				case 'artist': 
					$data['name'] 	= $result['ArtistName']; 
					$data['f_name_link'] = "<a href=\"" . $result['URI'] . "\">" . $data['name'] . "</a>"; 
					$object_ids[] = Artist::construct_from_array($data); 	
				break;
			} 
		} // end foreach

		require_once Config::get('prefix') . '/templates/show_artists.inc.php'; 	

	break;
	/* Show their stats */
	default: 
		/* Here's looking at you kid! */
		$working_user = $GLOBALS['user'];

		/* Pull favs */
		$favorite_artists	= $working_user->get_favorites('artist');
		$favorite_albums	= $working_user->get_favorites('album');
		$favorite_songs		= $working_user->get_favorites('song');

		require_once Config::get('prefix') . '/templates/show_user_stats.inc.php';

		// Onlu do this is ratings are on 
		if (Config::get('ratings')) { 
			/* Build Recommendations from Ratings */
			$recommended_artists	= $working_user->get_recommendations('artist');
			$recommended_albums	= $working_user->get_recommendations('albums');
			$recommended_songs	= $working_user->get_recommendations('song');
	
			require_once Config::get('prefix') . '/templates/show_user_recommendations.inc.php';
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
