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

<?php show_box_top(_('Edit Album')); ?>
<form name="edit_album" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/flag.php?action=edit_album">
<table class="tabledata">
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
		<input type="hidden" name="album_id" value="<?php echo $album->id; ?>" />
		<input type="submit" value="<?php echo _('Update Album'); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
