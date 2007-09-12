<?php
/*
 Copyright (c) 2001 - 2007 Ampache.org
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
/**
 * Playlist Box
 * This box is used for actions on the main screen and on a specific playlist page
 * It changes depending on where it is 
 */
$web_path = Config::get('web_path'); 
?>
<?php show_box_top($playlist->name . ' ' . _('Playlist')); ?>
		<ul class="text-action">
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=normalize_tracks&amp;playlist_id=<?php echo $playlist->id; ?>"><?php echo _('Normalize Tracks'); ?></a></li>
		<li><?php echo Ajax::text('?action=basket&type=playlist&id=' . $playlist->id,_('Play All'),'play_playlist'); ?></li>
		<li><?php echo Ajax::text('?action=basket&type=playlist_random&id=' . $playlist->id,_('Play Random'),'play_playlist'); ?></li>
		</ul>
<?php show_box_bottom(); ?>
<?php show_box_top(); ?>
<div id="browse_content">
<?php 
	$object_ids = $playlist->get_items(); 
	require_once Config::get('prefix') . '/templates/show_playlist_songs.inc.php'; 
?>
</div>
<?php show_box_bottom(); ?>
