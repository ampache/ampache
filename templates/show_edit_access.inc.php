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
<?php UI::show_box_top(T_('Edit Access Control List')); ?>
<form name="edit_access" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=update_record&access_id=<?php echo intval($access->id); ?>">
    <table class="tabledata" cellspacing="0" cellpadding="0">
        <tr>
            <td><?php echo T_('Name'); ?>: </td>
            <td colspan="3"><input type="text" name="name" value="<?php echo scrub_out($access->name); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('ACL Type'); ?>: </td>
            <td colspan="3">
                <select name="type">
                <?php $name = 'sl_' . $access->type; ${$name} = ' selected="selected"'; ?>
                    <option value="stream"<?php echo $sl_stream; ?>><?php echo T_('Stream Access'); ?></option>
                    <option value="interface"<?php echo $sl_interface; ?>><?php echo T_('Web Interface'); ?></option>
                    <option value="network"<?php echo $sl_network; ?>><?php echo T_('Local Network Definition'); ?></option>
                    <option value="rpc"<?php echo $sl_rpc; ?>><?php echo T_('API/RPC'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="4"><h3><?php echo T_('IPv4 or IPv6 Addresses'); ?></h3>
                <span class="information">(255.255.255.255) / (ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff)</span>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Start'); ?>:</td>
            <td>
                <?php Error::display('start'); ?>
                <input type="text" name="start" value="<?php echo $access->f_start; ?>" />
            </td>
            <td><?php echo T_('End'); ?>:</td>
            <td>
                <?php Error::display('end'); ?>
                <input type="text" name="end" value="<?php echo $access->f_end; ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo T_('User'); ?>:</td>
            <td colspan="3">
                <?php show_user_select('user',$access->user); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Level'); ?>:</td>
            <td colspan="3">
                <?php $name = 'level_' . $access->level; ${$name} = 'checked="checked"'; ?>
                <input type="radio" name="level" value="5"  <?php echo $level_5;  ?>><?php echo T_('View'); ?>
                <input type="radio" name="level" value="25" <?php echo $level_25; ?>><?php echo T_('Read'); ?>
                <input type="radio" name="level" value="50" <?php echo $level_50; ?>><?php echo T_('Read/Write'); ?>
                <input type="radio" name="level" value="75" <?php echo $level_75; ?>><?php echo T_('All'); ?>
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('edit_acl'); ?>
        <input type="submit" value="<?php echo T_('Update'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
