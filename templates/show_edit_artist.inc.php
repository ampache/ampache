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

<?php show_box_top(_('Edit Artist')); ?>
<form name="edit_artist" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/flag.php?action=edit_artist">
<table class="tabledata">
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Name'); ?></td>
	<td>
		<input type="text" name="name" value="<?php echo scrub_out($artist->name); ?>">
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
		<input type="hidden" name="artist_id" value="<?php echo $artist->id; ?>" />
		<input type="submit" value="<?php echo _('Update Artist'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
