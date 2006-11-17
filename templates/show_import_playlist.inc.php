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
?>
<form method="post" name="import_playlist" action="<?php echo conf('web_path'); ?>/playlist.php" enctype="multipart/form-data">
<?php show_box_top(_('Importing a Playlist from a File')); ?>
<table border="0" cellpadding="0" cellspacing="0">
<tr>
        <td>
		<?php echo _('Filename'); ?>:
		<?php $GLOBALS['error']->print_error('filename'); ?>
	</td>
	<td><input type="file" name="filename" value="<?php echo scrub_out($_REQUEST['filename']); ?>" size="45" /></td>	
</tr>
<tr>
	<td>
		<?php echo _('Playlist Type'); ?>
	</td>
	<td>
		<select name="playlist_type">
			<option value="m3u">M3U</option>
<!--			<option name="pls">PLS</option> -->
		</select>
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>
		<input type="hidden" name="action" value="import_playlist" />
		<input type="submit" value="<?php echo _('Import Playlist'); ?>" />
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
</form>
