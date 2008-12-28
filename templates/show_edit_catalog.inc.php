<?php
/*

 Copyright (c) Ampache.org
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
<?php show_box_top(sprintf(_('Settings for %s') , $catalog->name . ' (' . $catalog->path . ')')); ?>
<form method="post" action="<?php echo Config::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
<table cellspacing="0" cellpadding="0">
<tr>
	<td><?php echo _('Name'); ?>:</td>
	<td><input size="60" type="text" name="name" value="<?php echo scrub_out($catalog->name); ?>"></input></td>
	<td style="vertical-align:top; font-family: monospace;" rowspan="5">
		<strong><?php echo _('Auto-inserted Fields'); ?>:</strong><br />
		%A = <?php echo _('album name'); ?><br />
		%a = <?php echo _('artist name'); ?><br />
		%c = <?php echo _('id3 comment'); ?><br />
		%T = <?php echo _('track number (padded with leading 0)'); ?><br />
		%t = <?php echo _('song title'); ?><br />
		%y = <?php echo _('year'); ?><br />
		%o = <?php echo _('other'); ?><br /> 
	</td>
</tr>
<tr>
	<td><?php echo _('Catalog Type'); ?></td>
	<td><?php echo scrub_out(ucfirst($catalog->catalog_type)); ?></td>
</tr>
<tr>
	<td><?php echo _('XML-RPC Key'); ?>:</td>
	<td>
		<input size="30" type="text" name="key" value="<?php echo scrub_out($catalog->key); ?>" />*<span class="error"><?php echo _('Required for Remote Catalogs'); ?></span>
	</td>
</tr>
<tr>
	<td><?php echo _('Filename pattern'); ?>:</td>
	<td>
		<input size="60" type="text" name="rename_pattern" value="<?php echo scrub_out($catalog->rename_pattern); ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _('Folder Pattern'); ?>:<br /><?php echo _('(no leading or ending \'/\')'); ?>
	</td>
	<td>
		<input size="60" type="text" name="sort_pattern" value="<?php echo scrub_out($catalog->sort_pattern);?>" />
	</td>
</tr>
</table>
<div class="formValidation">
	<input type="hidden" name="catalog_id" value="<?php echo scrub_out($catalog->id); ?>" />
	<input type="hidden" name="action" value="update_catalog_settings" />
	<input type="submit" value="<?php echo _('Save Catalog Settings'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
