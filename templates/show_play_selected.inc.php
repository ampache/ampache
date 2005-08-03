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
?>
<script type="text/javascript" language="javascript">
<!--
function ToPlaylist(action)
{
	document.songs.action = "<?php echo conf('web_path'); ?>/playlist.php?action=" + action;
	document.songs.submit();			// Submit the page
	return true;
}

function ToSong(action)
{
	document.songs.action = "<?php echo conf('web_path'); ?>/song.php?action=" + action;
	document.songs.submit();			// Submit the page
	return true;
}
function ToBatch(action)
{
	document.songs.action = "<?php echo conf('web_path'); ?>/batch.php?action=" + action;
	document.songs.submit();
	return true;
}
-->
</script>
<table border="0" cellpadding="14" cellspacing="0" class="text-box">
<tr align="left">
        <td>
                <input class="button" type="button" name="super_action" value="<?php echo _("Play Selected"); ?>" onclick="return ToSong('play_selected');" />
		<?php if (batch_ok()) { ?>
		&nbsp;&nbsp;
		<input class="button" type="button" name="super_action" value="<?php echo _("Download Selected"); ?>" onclick="return ToBatch('download_selected');" />
		<? } ?>
<!--                <input class="button" type="button" name="super_action" value="<?php echo _("Flag Selected"); ?>" />
                <input class="button" type="button" name="super_action" value="<?php echo _("Edit Selected"); ?>" />
-->
        </td>
</tr>
<?php  if ($GLOBALS['playlist_id']) { ?>
<tr>
        <td>
                <input class="button" type="button" name="super_action" value="<?php echo _("Set Track Numbers"); ?>" onclick="return ToPlaylist('set_track_numbers');" />
                <input class="button" type="button" name="super_action" value="<?php echo _("Remove Selected Tracks"); ?>" onclick="return ToPlaylist('remove_song');" />
        </td>
</tr>
<?php  } ?>
<tr align="center">
        <td colspan="2">
                <?php echo _("Playlist"); ?>: <input type="button" name="super_action" value="<?php echo _("Add to"); ?>" onclick="return ToPlaylist('add_to');" />
                <?php  show_playlist_dropdown($GLOBALS['playlist_id']); ?>
                <input class="button" type="button" name="super_action" value="<?php echo _("View"); ?>" onclick="return ToPlaylist('view');" />
                <input class="button" type="button" name="super_action" value="<?php echo _("Edit"); ?>" onclick="return ToPlaylist('edit');" />
        </td>
</tr>
</table>
