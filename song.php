<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

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

require 'lib/init.php';

show_header(); 

// Switch on Action
switch ($_REQUEST['action']) { 
	default: 
	case 'show_song': 
		$song = new Song($_REQUEST['song_id']); 
		$song->format(); 
		$song->fill_ext_info(); 
		require_once Config::get('prefix') . '/templates/show_song.inc.php'; 
		// does user want to display lyrics?
		$show_lyrics = Config::get('show_lyrics');
		if($show_lyrics == 1) {
			$lyric = new Artist();
			$return = $lyric->get_song_lyrics($song->id, ucwords($song->f_artist), ucwords($song->title));
			$link = '<a href="http://lyricwiki.org/' . rawurlencode(ucwords($song->f_artist)) . ':' . rawurlencode(ucwords($song->title)) . '" target="_blank">';
			$link .= sprintf(_('%1$s - %2$s Lyrics Detail'), ucwords($song->f_artist), ucwords($song->title));
			$link .= "</a><br /><br />";
			require_once Config::get('prefix') . '/templates/show_lyrics.inc.php';
		}
	break; 
} // end data collection 

show_footer(); 

?>
