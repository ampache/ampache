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
?>

<?php show_box_top(_('Edit Song')); ?>
<form name="edit_song" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/flag.php">
<table>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('File'); ?>:</td>
	<td><?php echo scrub_out($song->file); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Title'); ?></td>
	<td>
		<input type="text" name="title" value="<?php echo scrub_out($song->title); ?>" size="45" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Album'); ?></td>
	<td>
		<?php show_album_select('album',$song->album); ?>
		<br /><?php echo _('OR'); ?><br />
		<input type="text" name="album_string" value="<?php echo scrub_out($song->get_album_name()); ?>" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Artist'); ?></td>
	<td>
		<?php show_artist_select('artist',$song->artist); ?>
		<br /><?php echo _('OR'); ?><br />
		<input type="text" name="artist_string" value="<?php echo scrub_out($song->get_artist_name()); ?>" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Track'); ?></td>
	<td>
		<input type="text" name="track" value="<?php echo scrub_out($song->track); ?>" size="3" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Year'); ?></td>
	<td>
		<input type="text" name="year" value="<?php echo scrub_out($song->year); ?>" size="5" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Comment'); ?></td>
	<td>
		<input type="text" name="comment" value="<?php echo scrub_out($song->comment); ?>" size="45" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
		<input type="checkbox" name="flag" value="1" checked="checked" /> <?php echo _('Flag for Retagging'); ?>
	</td>
</tr>
</table>
<div class="formValidation">
		<input type="hidden" name="song_id" value="<?php echo $song->id; ?>" />
		<input type="hidden" name="action" value="edit_song" />
		<input type="submit" value="<?php echo _('Update Song'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
