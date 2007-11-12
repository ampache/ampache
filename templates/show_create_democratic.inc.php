<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

show_box_top(_('Create Democratic Playlist')); ?>
<form method="post" action="<?php echo Config::get('web_path'); ?>/democratic.php?action=create" enctype="multipart/form-data">
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td><?php echo _('Name'); ?></td>
	<td><input type="textbox" name="name" value="" /></td>
</tr>
<tr>
	<td><?php echo _('Base Playlist'); ?></td>
	<td><?php show_playlist_select('democratic'); ?></td>
</tr>
<tr>
	<td><?php echo _('Make Default'); ?></td>
	<td><input type="checkbox" name="make_default" value="1" /></td>
</tr>
<tr>
	<td>
		<input type="submit" value="<?php echo _('Create'); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
