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

$web_path = conf('web_path');

?>
<form method="post" action="<?php echo $web_path; ?>/playlist.php" enctype="multipart/form-data">
<?php show_box_top(_('Editing Playlist')); ?>
<table>
<tr>
	<td><?php echo _('Name'); ?>:</td>
	<td align="left">
	<input type="text" name="playlist_name" value="<?php echo scrub_out($playlist->name); ?>" size="<?php echo strlen($playlist->name)+3; ?>" />
	</td>
</tr>
<tr>
	<td valign="top"><?php echo _('Type'); ?>:</td>
	<td>
	<?php $select_name = 'selected_' . $playlist->type; ${$select_name} = "checked=\"checked\""; ?>
	<input type="radio" name="type" value="public" <?php echo $selected_public; ?>/><?php echo _('Public'); ?><br />
	<input type="radio" name="type" value="private" <?php echo $selected_private; ?>/><?php echo _('Private'); ?><br />
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
	<input type="hidden" name="playlist_id" value="<?php echo $playlist->id; ?>" />
	<input type="hidden" name="action" value="update_playlist" />
	<input type="submit" value="<?php echo _('Update'); ?>" />
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
</form>
