<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Add Catalog
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

$default_rename = "%a - %T - %t";
$default_sort = "%a/%A";
?>
<?php show_box_top(_('Add a Catalog'), 'box box_add_catalog'); ?>
<p><?php echo _("In the form below enter either a local path (i.e. /data/music) or the URL to a remote Ampache installation (i.e http://theotherampache.com)"); ?></p>
<?php Error::display('general'); ?>
<form name="update_catalog" method="post" action="<?php echo Config::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
	<td><?php echo _('Catalog Name'); ?>: </td>
	<td><input size="60" type="text" name="name" value="<?php echo scrub_out($_POST['name']); ?>" /></td>
	<td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
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
	<td><?php echo _('Path'); ?>: </td>
	<td><input size="60" type="text" name="path" value="<?php echo scrub_out($_POST['path']); ?>" /></td>
</tr>
<tr>
	<td><?php echo _('Catalog Type'); ?>: </td>
	<td>
		<select name="type">
			<option value="local"><?php echo _('Local'); ?></option>
			<option value="remote"><?php echo _('Remote'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?php echo _('Remote Catalog Username'); ?>: </td>
	<td><input size="30" type="text" name="remote_username" value="" /><span class="error">*<?php echo _('Required for Remote Catalogs'); ?></span></td>
</tr>
<tr>
	<td><?php echo _('Remote Catalog Password'); ?>: </td>
	<td><input size="30" type="password" name="remote_password" value="" /><span class="error">*<?php echo _('Required for Remote Catalogs'); ?></span></td>
</tr>
<tr>
	<td><?php echo _('Filename Pattern'); ?>: </td>
	<td><input size="60" type="text" name="rename_pattern" value="<?php echo $default_rename; ?>" /></td>
</tr>

<tr>
	<td><?php echo _('Folder Pattern'); ?>:<br /><?php echo _("(no leading or ending '/')"); ?></td>
	<td valign="top"><input size="60" type="text" name="sort_pattern" value="<?php echo $default_sort; ?>" /></td>
</tr>

<tr>
	<td valign="top"><?php echo _('Gather Album Art'); ?>:</td>
	<td><input type="checkbox" name="gather_art" value="1" /></td>
</tr>
<tr>
	<td valign="top"><?php echo _('Build Playlists from m3u Files'); ?>:</td>
	<td><input type="checkbox" name="parse_m3u" value="1" /></td>
</tr>
</table>
<div class="formValidation">
  <input type="hidden" name="action" value="add_catalog" />
  <?php echo Core::form_register('add_catalog'); ?>
  <input class="button" type="submit" value="<?php echo _('Add Catalog'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
