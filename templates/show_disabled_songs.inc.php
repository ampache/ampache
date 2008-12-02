<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
?>
<br />
<form name="songs" method="post" action="<?php echo conf('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_select" />
  <col id="col_song" />
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_filename" />
  <col id="col_additiontime" />
</colgroup>
<tr class="th-top">
	<th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></th>
	<th class="cel_song"><?php echo _('Title'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_filename"><?php echo _('Filename'); ?></th>
	<th class="cel_additiontime"><?php echo _('Addition Time'); ?></th>
</tr>
<?php foreach ($songs as $song) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td class="cel_select"><input type="checkbox" name="song[]" value="<?php echo $song->id; ?>" /></td>
		<td class="cel_song"><?php echo $song->title; ?></td>
		<td class="cel_album"><?php echo $song->get_album_name($song->album); ?></td>
		<td class="cel_artist"><?php echo $song->get_artist_name($song->album); ?></td>
		<td class="cel_filename"><?php echo $song->file; ?></td>
		<td class="cel_additiontime"><?php echo date("h:i:s, m/d/y",$song->addition_time); ?></td>
	
	</tr>
<?php } if (!count($songs)) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td colspan="7"><span class="error"><?php echo _('No Records Found'); ?></span></td>
	</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_select"><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></th>
	<th class="cel_song"><?php echo _('Title'); ?></th>
	<th class="cel_album"><?php echo _('Album'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_filename"><?php echo _('Filename'); ?></th>
	<th class="cel_additiontime"><?php echo _('Addition Time'); ?></th>
</tr>
</table>
<div class="formValidation">
		<input class="button" type="submit" value="<?php echo _('Remove'); ?>" />&nbsp;&nbsp;
		<input type="hidden" name="action" value="remove_disabled" />
</div>
</form>
