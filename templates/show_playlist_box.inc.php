<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

$web_path = conf('web_path'); 
$playlist_id = scrub_out($_REQUEST['playlist_id']);
$title = _('Playlist Actions');
?>
<?php require (conf('prefix') . '/templates/show_box_top.inc.php'); ?>
		<ul class="text-action">
		<?php if ($_REQUEST['playlist_id']) { ?>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=edit&amp;playlist_id=<?php echo $playlist_id; ?>"><?php echo _('Edit Playlist'); ?></a></li>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=normalize_tracks&amp;playlist_id=<?php echo $playlist_id; ?>"><?php echo _('Normalize Tracks'); ?></a></li>
		<li><a href="<?php echo $web_path; ?>/song.php?action=play_selected&amp;playlist_id=<?php echo $playlist_id; ?>"><?php echo _('Play This Playlist'); ?></a></li>
		<li><a href="<?php echo $web_path; ?>/song.php?action=playlist_random&amp;playlist_id=<?php echo $playlist_id; ?>"><?php echo _('Play Random'); ?></a></li>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=show_delete_playlist&amp;playlist_id=<?php echo $playlist_id; ?>"><?php echo _('Delete This Playlist'); ?></a></li>
		<?php } else { ?>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=show_import_playlist"><?php echo _('Import From File'); ?></a></li>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=new"><?php echo _('Create New Playlist'); ?></a></li>
		<?php if ($GLOBALS['user']->has_access(100)) { ?>
		<li><a href="<?php echo $web_path; ?>/playlist.php?action=prune_empty"><?php echo _('Delete Empty Playlists'); ?></a></li>
		<?php } ?>
		<?php } ?>	
		</ul>
<?php require (conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
