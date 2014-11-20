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
<?php UI::show_box_top(T_('Configure License')); ?>
<form method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/license.php?action=edit">
<?php if (isset($license)) { ?>
<input type="hidden" name="license_id" value="<?php echo $license->id; ?>" />
<?php } ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
    <td><input type="text" name="name" value="<?php if (isset($license)) echo $license->name; ?>" autofocus /></td>
</tr>
<tr>
    <td><?php echo T_('Description:'); ?></td>
    <td><textarea rows="5" cols="70"  maxlength="140" name="description"><?php if (isset($license)) echo $license->description; ?></textarea></td>
</tr>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('External Link') ?></td>
    <td><input type="text" name="external_link" value="<?php if (isset($license)) echo $license->external_link; ?>" /></td>
</tr>
<tr>
    <td>
        <input type="submit" value="<?php echo T_('Confirm'); ?>" />
    </td>
</tr>
</table>
</form>
<?php UI::show_box_bottom(); ?>
