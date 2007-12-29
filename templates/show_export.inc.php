<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License. 

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
show_box_top(_('Export Catalog')); ?>
<form name="duplicates" action="<?php echo Config::get('web_path'); ?>/admin/export.php?action=export" method="post" enctype="multipart/form-data" >
<table cellspacing="0" cellpadding="3">
<tr>
	<td valign="top"><strong><?php echo _('Catalog'); ?>:</strong></td>
	<td>
		<select id="export_catalog" name="export_catalog" width="150" style="width: 150px">
			<option value="">(all)</option>
<?php
		$catalog_ids = Catalog::get_catalogs(); 
		foreach ($catalog_ids as $cat_id) {
			$cat = new Catalog($cat_id);
?>
			<option value="<?php echo $cat->id; ?>" <?php if($_REQUEST['export_catalog']==$cat->id) echo "selected=\"selected\"" ?>><?php echo $cat->name; ?></option>
<?php
		}
?>
		</select>
	</td>
</tr>
<tr>
	<td valign="top"><strong><?php echo _('Format'); ?>:</strong></td>
	<td>
		<select id="export_format" name="export_format" width="150" style="width: 150px">
			<option value="itunes" <?php if($_REQUEST['export_format']=='itunes') echo "selected=\"selected\"" ?>>iTunes</option>
		</select>
	</td>
</tr>
</table>
<div class="formValidation">
      <input type="submit" value="<?php echo _('Export'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
