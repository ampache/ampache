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
<?php show_box_top(_('Create a new playlist')); ?>
<form name="songs" method="post" action="<?php echo conf('web_path'); ?>/playlist.php">
<table>
<tr>
	<td><?php echo _('Name'); ?>:</td>
	<td><input type="text" name="playlist_name" size="20" /></td>
</tr>
<tr>
	<td><?php echo _('Type'); ?>:</td>
	<td>
	<select name="type">
	<option value="private"> Private </option>
	<option value="public"> Public </option>
	</select>
	</td>
</tr>
</table>
<div class="formValidation">
	<input class="button" type="submit" value="<?php echo _('Create'); ?>" />
	<input type="hidden" name="action" value="Create" />
</div>
</form>
<?php show_box_bottom(); ?>
