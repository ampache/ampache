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

<?php UI::show_box_top(T_('Edit Artist')); ?>
<form name="edit_artist" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/flag.php?action=edit_artist">
<table class="tabledata">
<tr class="<?php echo UI::flip_class(); ?>">
    <td><?php echo T_('Name'); ?></td>
    <td>
        <input type="text" name="name" value="<?php echo scrub_out($artist->name); ?>">
    </td>
</tr>
<tr class="<?php echo UI::flip_class(); ?>">
    <td>&nbsp;</td>
    <td>
        <input type="checkbox" name="flag" value="1" checked="checked" /> <?php echo T_('Flag for Retagging'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
        <input type="hidden" name="artist_id" value="<?php echo $artist->id; ?>" />
        <input type="submit" value="<?php echo T_('Update Artist'); ?>" />
</div>
</form>
<?php UI::show_box_bottom(); ?>
