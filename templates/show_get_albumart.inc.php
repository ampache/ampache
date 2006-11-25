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
<?php show_box_top(_('Customize Search')); ?>
<form enctype="multipart/form-data" name="coverart" method="post" action="<?php echo conf('web_path'); ?>/albums.php?action=find_art&album_id=<?php echo $album->id; ?>&artist_name=<?php echo $_REQUEST['artist_name'];?>&album_name=<?php echo $_REQUEST['album_name']; ?>&cover=<?php echo scrub_out($_REQUEST['cover']); ?>" style="Display:inline;">
<table>
<tr>
</tr>
<tr>
	<td>
		<?php echo _('Artist'); ?>&nbsp;
	</td>
	<td>
		<input type="text" size="20" id="artist_name" name="artist_name" value="<?php echo scrub_out($artistname); ?>" />
	</td>
</tr>
<tr>
	<td>
	 	<?php echo _('Album'); ?>&nbsp;
	</td>
	<td>
		<input type="text" size="20" id="album_name" name="album_name" value="<?php echo scrub_out($albumname); ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _('Direct URL to Image'); ?>
	</td>
	<td>
		<input type="text" size="40" id="cover" name="cover" value="" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _('Local Image'); ?>
	</td>
	<td>
		<input type="file" size="40" id="file" name="file" value="" />
	</td>
</tr>

<tr>
	<td>
		<input type="hidden" name="action" value="find_art" />
		<input type="hidden" name="album_id" value="<?php echo $album->id; ?>" />
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo conf('max_upload_size'); ?>" />
		<input type="submit" value="<?php echo _('Get Art'); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
