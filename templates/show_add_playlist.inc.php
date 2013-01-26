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
<?php UI::show_box_top(T_('Create a new playlist')); ?>
<form name="songs" method="post" action="<?php echo Config::get('web_path'); ?>/playlist.php">
<table>
<tr>
    <td><?php echo T_('Name'); ?>:</td>
    <td><input type="text" name="playlist_name" size="20" /></td>
</tr>
<tr>
    <td><?php echo T_('Type'); ?>:</td>
    <td>
    <select name="type">
    <option value="private"> Private </option>
    <option value="public"> Public </option>
    </select>
    </td>
</tr>
</table>
<div class="formValidation">
    <input class="button" type="submit" value="<?php echo T_('Create'); ?>" />
    <input type="hidden" name="action" value="Create" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
