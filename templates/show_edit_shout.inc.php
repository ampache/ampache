<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
<?php UI::show_box_top(T_('Edit existing Shoutbox Post')); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/shout.php?action=edit_shout">
<input type="hidden" name="shout_id" value="<?php echo $shout->id; ?>" />
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
    <td><strong><?php /* HINT: Client link, Object link */ printf(T_('Created by: %s for %s'), $client->f_link, $object->f_link); ?></strong>
<tr>
<tr>
    <td><strong><?php echo T_('Comment:'); ?></strong>
</tr>
<tr>
    <td><textarea rows="5" cols="70"  maxlength="140" name="comment"><?php echo $shout->text; ?></textarea></td>
</tr>
<tr>
    <td><input type="checkbox" name="sticky" <?php if ($shout->sticky == "1") { echo "checked"; } ?>/> <strong><?php echo T_('Stick this comment'); ?></strong></td>
</tr>
<tr>
    <td>
        <input type="submit" value="<?php echo T_('Update'); ?>" />
    </td>
</tr>
</table>
</form>
<?php UI::show_box_bottom(); ?>
