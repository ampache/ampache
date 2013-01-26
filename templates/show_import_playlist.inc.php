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
?>
<?php UI::show_box_top(T_('Importing a Playlist from a File'), 'box box_import_playlist'); ?>
<form method="post" name="import_playlist" action="<?php echo Config::get('web_path'); ?>/playlist.php" enctype="multipart/form-data">
<table cellpadding="0" cellspacing="0">
<tr>
        <td>
        <?php echo T_('Filename'); ?>:
    </td>
    <td><input type="file" name="filename" value="<?php echo scrub_out($_REQUEST['filename']); ?>" size="45" /></td>
</tr>
<tr>
    <td>
        <?php echo T_('Playlist Type'); ?>
    </td>
    <td>
        <select name="playlist_type">
            <option value="m3u">M3U</option>
<!--            <option name="pls">PLS</option> -->
        </select>
    </td>
</tr>
</table>
<div class="formValidation">
        <input type="hidden" name="action" value="import_playlist" />
        <input type="submit" value="<?php echo T_('Import Playlist'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>

