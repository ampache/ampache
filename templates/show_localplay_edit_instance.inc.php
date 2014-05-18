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
<?php UI::show_box_top(T_('Edit Localplay Instance'), 'box box_localplay_edit_instance'); ?>
<form method="post" action="<?php echo AmpConfig::get('web_path'); ?>/localplay.php?action=update_instance&amp;instance=<?php echo intval($_REQUEST['instance']); ?>">
<table cellpadding="3" cellspacing="0" class="tabledata">
<?php foreach ($fields as $key=>$field) { ?>
<tr>
    <td><?php echo $field['description']; ?></td>
    <td><input type="text" name="<?php echo $key; ?>" value="<?php echo scrub_out($instance[$key]); ?>" /></td>
</tr>
<?php } ?>
</table>
    <div class="formValidation">
        <input type="submit" value="<?php echo T_('Update Instance'); ?>" />
  </div>
</form>
<?php UI::show_box_bottom(); ?>
