<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php UI::show_box_top(T_('Add Access Control List'), 'box box_add_access'); ?>
<?php AmpError::display('general');
$apirpc       = T_('API/RPC');
$localnetwork = T_('Local Network Definition');
$streamaccess = T_('Stream Access');
$all          = T_('All'); ?>
<form name="update_access" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=add_host">
    <table class="tabledata">
        <tr>
            <td><?php echo T_('Name') . ':'; ?></td>
            <td><input type="text" name="name" value="<?php echo scrub_out(Core::get_request('name')); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo T_('Level') . ':'; ?></td>
            <td><input name="level" type="radio" checked="checked" value="5" /> <?php echo T_('View'); ?>
                <input name="level" type="radio" value="25" /> <?php echo T_('Read'); ?>
                <input name="level" type="radio" value="50" /> <?php echo T_('Read/Write'); ?>
                <input name="level" type="radio" value="75" /> <?php echo $all; ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('User') . ':'; ?></td>
            <td>
                <?php show_user_select('user'); ?>
            </td>
        </tr>
        <tr>
            <td><?php echo T_('Type') . ':'; ?></td>
            <td>
        <?php if ($action == 'show_add_rpc') { ?>
                <input type="hidden" name="type" value="rpc" />
                <select name="addtype">
                    <option value="rpc"><?php echo $apirpc; ?></option>
                    <option selected="selected" value="stream"><?php echo $apirpc . ' + ' . $streamaccess; ?></option>
                    <option value="all"><?php echo $apirpc . ' + ' . $all; ?></option>
        <?php
} else {
    if ($action == 'show_add_local') { ?>
                <input type="hidden" name="type" value="local" />
                <select name="addtype">
                    <option value="network"><?php echo $localnetwork; ?></option>
                    <option value="stream"><?php echo $localnetwork . ' + ' . $streamaccess; ?></option>
                    <option selected="selected" value="all"><?php echo $localnetwork . ' + ' . $all; ?></option>
        <?php
        } else { ?>
                <select name="type">
                    <option selected="selected" value="stream"><?php echo $streamaccess; ?></option>
                    <option value="interface"><?php echo T_('Web Interface'); ?></option>
                    <option value="network"><?php echo $localnetwork; ?></option>
                    <option value="rpc"><?php echo $apirpc; ?></option>
        <?php
        }
} ?>
                </select>
            </td>
        </tr>
    </table>
    &nbsp;
    <table class="tabledata">
        <tr>
            <td><h3><?php echo T_('IPv4 or IPv6 Addresses'); ?></h3>
                <span class="information">(255.255.255.255) / (ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff)</span>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('Start'); ?>:
                    <?php AmpError::display('start'); ?>
                    <input type="text" name="start" value="<?php
                if ($action == 'show_add_current') {
                    echo scrub_out(Core::get_server('REMOTE_ADDR'));
                } else {
                    echo scrub_out(Core::get_request('start'));
                } ?>" /></td>
            <td>
                <?php echo T_('End'); ?>:
                    <?php AmpError::display('end'); ?>
                    <input type="text" name="end" value="<?php
                    if ($action == 'show_add_current') {
                        echo scrub_out(Core::get_server('REMOTE_ADDR'));
                    } else {
                        echo scrub_out(Core::get_request('end'));
                    } ?>" /></td>
        </tr>
    </table>
    <div class="formValidation">
        <?php echo Core::form_register('add_acl'); ?>
        <input class="button" type="submit" value="<?php echo T_('Create ACL'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
