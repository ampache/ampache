<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
 * Show Playlists
 * This takes an array of playlists and displays them for the default view of
 * /playlists.php $type is always passed
 */
?>
<h3><?php echo $type . ' ' . _('Playlists'); ?></h3>
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header">
	<th><?php echo _('Playlist Name'); ?></th>
	<th><?php echo _('# Songs'); ?></th>
	<th><?php echo _('Owner'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
<?php 
	foreach ($playlists as $playlist) { 
		$playlist_user = new User($playlist->user);
		$count = $playlist->get_song_count(); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td>
		<a href="<?php echo conf('web_path'); ?>/playlist.php?action=show_playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
		<?php echo scrub_out($playlist->name); ?>
		</a>
	</td>
	<td><?php echo $count; ?></td>
	<td><?php echo scrub_out($playlist_user->fullname); ?></td>
	<td>
		| <a href="<?php echo conf('web_path'); ?>/playlist.php?action=show_playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
		<?php echo _('View'); ?>
		</a>	
		<?php if ($GLOBALS['user']->username == $playlist->user || $GLOBALS['user']->has_access(100)) { ?>
			| <a href="<?php echo conf('web_path'); ?>/playlist.php?action=edit&amp;playlist_id=<?php echo $playlist->id; ?>">
			<?php echo _('Edit'); ?>
			</a>
			| <a href="<?php echo conf('web_path'); ?>/playlist.php?action=show_delete_playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
			<?php echo _('Delete'); ?>
			</a>
		<?php } ?>
		<?php if ($count > 0) { ?>
			| <a href="<?php echo conf('web_path'); ?>/song.php?action=playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
			<?php echo _('Play'); ?>
			</a>
			| <a href="<?php echo conf('web_path'); ?>/song.php?action=playlist_random&amp;playlist_id=<?php echo $playlist->id; ?>">
			<?php echo _('Random'); ?>
			</a>
		<?php } ?>
		<?php if (batch_ok()) { ?>
			| <a href="<?php echo conf('web_path'); ?>/batch.php?action=pl&amp;id=<?php echo $playlist->id; ?>">
			<?php echo _('Download'); ?>
			</a>
		<?php } ?>
		|
	</td>
</tr>
<?php } ?>
</table>
