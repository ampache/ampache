<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 */

$default_rename = "%a - %T - %t";
$default_sort = "%a/%A";
?>
<?php UI::show_box_top(T_('Add a Catalog'), 'box box_add_catalog'); ?>
<p><?php echo T_("In the form below enter either a local path (i.e. /data/music) or the URL to a remote Ampache installation (i.e http://theotherampache.com)"); ?></p>
<?php Error::display('general'); ?>
<form name="update_catalog" method="post" action="<?php echo Config::get('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data">
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
    <td><?php echo T_('Catalog Name'); ?>: </td>
    <td><input size="60" type="text" name="name" value="<?php echo scrub_out($_POST['name']); ?>" /></td>
    <td style="vertical-align:top; font-family: monospace;" rowspan="6" id="patterns_example">
        <strong><?php echo T_('Auto-inserted Fields'); ?>:</strong><br />
        <span class="format-specifier">%A</span> = <?php echo T_('album name'); ?><br />
        <span class="format-specifier">%a</span> = <?php echo T_('artist name'); ?><br />
        <span class="format-specifier">%c</span> = <?php echo T_('id3 comment'); ?><br />
        <span class="format-specifier">%T</span> = <?php echo T_('track number (padded with leading 0)'); ?><br />
        <span class="format-specifier">%t</span> = <?php echo T_('song title'); ?><br />
        <span class="format-specifier">%y</span> = <?php echo T_('year'); ?><br />
        <span class="format-specifier">%o</span> = <?php echo T_('other'); ?><br />
    </td>
</tr>

<tr>
    <td><?php echo T_('Path'); ?>: </td>
    <td><input size="60" type="text" name="path" value="<?php echo scrub_out($_POST['path']); ?>" /></td>
</tr>
<tr>
    <td><?php echo T_('Catalog Type'); ?>: </td>
    <td>
        <select name="type">
            <option value="local"><?php echo T_('Local'); ?></option>
            <option value="remote"><?php echo T_('Remote'); ?></option>
        </select>
    </td>
</tr>
<tr>
    <td><?php echo T_('Remote Catalog Username'); ?>: </td>
    <td><input size="30" type="text" name="remote_username" value="<?php echo scrub_out($_POST['remote_username']); ?>" /><span class="error">*<?php echo T_('Required for Remote Catalogs'); ?></span></td>
</tr>
<tr>
    <td><?php echo T_('Remote Catalog Password'); ?>: </td>
    <td><input size="30" type="password" name="remote_password" value="" /><span class="error">*<?php echo T_('Required for Remote Catalogs'); ?></span></td>
</tr>
<tr>
    <td><?php echo T_('Filename Pattern'); ?>: </td>
    <td><input size="60" type="text" name="rename_pattern" value="<?php echo $default_rename; ?>" /></td>
</tr>

<tr>
    <td><?php echo T_('Folder Pattern'); ?>:<br /><?php echo T_("(no leading or ending '/')"); ?></td>
    <td valign="top"><input size="60" type="text" name="sort_pattern" value="<?php echo $default_sort; ?>" /></td>
</tr>

<tr>
    <td valign="top"><?php echo T_('Gather Album Art'); ?>:</td>
    <td><input type="checkbox" name="gather_art" value="1" /></td>
</tr>
<tr>
    <td valign="top"><?php echo T_('Build Playlists from m3u Files'); ?>:</td>
    <td><input type="checkbox" name="parse_m3u" value="1" /></td>
</tr>
</table>
<div class="formValidation">
  <input type="hidden" name="action" value="add_catalog" />
  <?php echo Core::form_register('add_catalog'); ?>
  <input class="button" type="submit" value="<?php echo T_('Add Catalog'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
