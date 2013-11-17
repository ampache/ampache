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
<?php UI::show_box_top(T_('Post to Shoutbox'), 'box box_add_shout'); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/shout.php?action=add_shout">
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
    <td><strong><?php echo T_('Comment:'); ?></strong>
</tr>
<tr>
    <td><textarea rows="5" cols="70" name="comment"></textarea></td>
</tr>
<?php if (Access::check('interface','50')) { ?>
<tr>
    <td><input type="checkbox" name="sticky" /> <strong><?php echo T_('Make Sticky'); ?></strong></td>
</tr>
<?php } ?>
<tr>
    <td>
        <?php echo Core::form_register('add_shout'); ?>
        <input type="hidden" name="object_id" value="<?php echo $object->id; ?>" />
        <input type="hidden" name="object_type" value="<?php echo strtolower(get_class($object)); ?>" />
        <input type="submit" value="<?php echo T_('Create'); ?>" />
    </td>
</tr>
</table>
</form>
<?php UI::show_box_bottom(); ?>
