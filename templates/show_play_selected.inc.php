<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
<table border="0" cellpadding="14" cellspacing="0" class="text-box">
<tr>
<td>
	<input class="button" type="button" value="<?php echo _('Play Selected'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/song.php?action=play_selected');" />
	<?php if (batch_ok()) { ?>
		&nbsp;&nbsp;
		<input class="button" type="button" value="<?php echo _('Download Selected'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/batch.php?action=download_selected');" />
	<?php } ?>
</td>
</tr>
<?php 
if (is_object($GLOBALS['playlist'])) { ?>
<tr>
<td>
	<input type="hidden" name="playlist_id" value="<?php echo $GLOBALS['playlist']->id; ?>" />
	<input class="button" type="button" value="<?php echo _('Set Track Numbers'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/playlist.php?action=set_track_numbers');" />
	<input class="button" type="button" value="<?php echo _('Remove Selected Tracks'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/playlist.php?action=remove_song');" />
</td>
</tr>
<?php } else { ?>
<tr>
<td>
	<?php echo _('Playlist'); ?>: <input type="button" value="<?php echo _('Add to'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/playlist.php?action=add_to');" />
	<?php  show_playlist_select($_SESSION['data']['playlist_id']); ?>
	<input class="button" type="button" value="<?php echo _('View'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/playlist.php?action=view');" />
	<input class="button" type="button" value="<?php echo _('Edit'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/playlist.php?action=edit');" />
</td>
</tr>
<?php } ?>
<?php if ($GLOBALS['user']->has_access('100')) { ?>
<tr>
<td>
	<select name="update_field">
		<option value="genre"><?php echo _('Genre'); ?></option> 
		<option value="album"><?php echo _('Album'); ?></option>
		<option value="artist"><?php echo _('Artist'); ?></option> 
		<option value="year"><?php echo _('Year'); ?></option> 
	</select>
	<input type="textbox" name="update_value" />
	<input class="button" type="button" value="<?php echo _('Update'); ?>" onclick="return SubmitToPage('songs','<?php echo $web_path; ?>/admin/flag.php?action=mass_update');" />
</td>
</tr>
<?php } ?>
</table>
