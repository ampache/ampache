<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/**
 * Show Genre
 * This shows a single genre and lets you pick between
 * albums/artists or songs
*/
$web_path = Config::get('web_path');
?>
<?php show_box_top(sprintf(_('Viewing %s Genre'), $genre->name)); ?>
		[<?php echo $genre->get_album_count(); ?>] 
		<a href="<?php echo $web_path; ?>/genre.php?action=show_albums&amp;genre_id=<?php echo $genre->id; ?>">
			<?php echo _('Albums'); ?></a><br />
		[<?php echo $genre->get_artist_count(); ?>]
		<a href="<?php echo $web_path; ?>/genre.php?action=show_artists&amp;genre_id=<?php echo $genre->id; ?>">
			<?php echo _('Artists'); ?></a><br />
		[<?php echo $genre->get_song_count(); ?>]
		<a href="<?php echo $web_path; ?>/genre.php?action=show_songs&amp;genre_id=<?php echo $genre->id; ?>">
			<?php echo _('Songs'); ?></a><br />
<?php show_box_bottom(); ?>
