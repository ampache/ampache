<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
<table class="tabledata" cellspacing="0">
<tr class="table-header">
	<td><a href="#" onclick="check_select('song'); return false;"><?php echo _('Select'); ?></a></td>
	<td><?php echo _('Title'); ?></td>
	<td><?php echo _('Album'); ?></td>
	<td><?php echo _('Artist'); ?></td>
	<td><?php echo _('Filename'); ?></td>
	<td><?php echo _('Addition Time'); ?></td>
</tr>
<?php foreach ($songs as $song) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td><input type="checkbox" name="song[]" value="<?php echo $song->id; ?>" /></td>
		<td><?php echo $song->title; ?></td>
		<td><?php echo $song->get_album_name($song->album); ?></td>
		<td><?php echo $song->get_artist_name($song->album); ?></td>
		<td><?php echo $song->file; ?></td>
		<td><?php echo date("h:i:s, m/d/y",$song->addition_time); ?></td>
	
	</tr>
<?php } if (!count($songs)) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td colspan="7"><span class="error"><?php echo _('No Records Found'); ?></span></td>
	</tr>
<?php } ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="7">
		<input class="button" type="submit" value="<?php echo _('Remove'); ?>" />&nbsp;&nbsp;
		<input type="hidden" name="action" value="remove_disabled" />
	</td>
</tr>
</table>
</form>
