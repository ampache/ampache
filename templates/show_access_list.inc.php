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
<?php UI::show_box_top(T_('Access Control'), 'box box_access_control');
$addcurrent = T_('Add Current Host');
$addrpc     = T_('Add API / RPC Host');
$addlocal   = T_('Add Local Network Definition'); ?>
<div id="information_actions" class="left-column">
<ul>
    <li>
        <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_add_current"><?php echo UI::get_icon('add_user', $addcurrent) . ' ' . $addcurrent; ?></a>
    </li>
    <li>
        <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_add_rpc"><?php echo UI::get_icon('cog', $addrpc) . ' ' . $addrpc; ?></a>
    </li>
    <li>
        <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_add_local"><?php echo UI::get_icon('home', $addlocal) . ' ' . $addlocal; ?></a>
    <li>
        <a class="option-list" href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_add_advanced"><?php echo UI::get_icon('add_key', T_('Advanced Add')) . ' ' . T_('Advanced Add'); ?></a>
    </li>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<?php UI::show_box_top(T_('Access Control Entries'), 'box box_access_entries'); ?>
<?php Ajax::start_container('browse_content', 'browse_content'); ?>
<?php if (count($list)) { ?>
<table class="tabledata">
<thead>
    <tr class="th-top">
        <th><?php echo T_('Name'); ?></th>
        <th><?php echo T_('Start Address'); ?></th>
        <th><?php echo T_('End Address'); ?></th>
        <th><?php echo T_('Level'); ?></th>
        <th><?php echo T_('User'); ?></th>
        <th><?php echo T_('Type'); ?></th>
        <th><?php echo T_('Action'); ?></th>
    </tr>
</thead>
<tbody>
<?php
    /* Start foreach List Item */
    foreach ($list as $access_id) {
        $access = new Access($access_id);
        $access->format(); ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td><?php echo scrub_out($access->name); ?></td>
    <td><?php echo $access->f_start; ?></td>
    <td><?php echo $access->f_end; ?></td>
    <td><?php echo $access->f_level; ?></td>
    <td><?php echo $access->f_user; ?></td>
    <td><?php echo $access->f_type; ?></td>
    <td>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_edit_record&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php echo UI::get_icon('edit', T_('Edit')); ?></a>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/admin/access.php?action=show_delete_record&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php echo UI::get_icon('delete', T_('Delete')); ?></a>
    </td>
</tr>
    <?php
    } // end foreach?>
</tbody>
</table>
<?php
} // end if count?>
<?php Ajax::end_container(); ?>
<?php UI::show_box_bottom(); ?>
