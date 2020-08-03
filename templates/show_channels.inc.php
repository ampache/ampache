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
 */
 ?>
<?php if ($browse->is_show_header()) {
     require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
 } ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="channel">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_id essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=channel&sort=id', '#', 'channel_sort_id'); ?></th>
            <th class="cel_name essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=channel&sort=name', T_('Name'), 'channel_sort_name'); ?></th>
            <th class="cel_interface essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=channel&sort=interface', T_('Interface'), 'channel_sort_interface'); ?></th>
            <th class="cel_port essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=channel&sort=port', T_('Port'), 'channel_sort_port'); ?></th>
            <th class="cel_data optional"><?php echo T_('Stream Source'); ?></th>
            <!--<th class="cel_random"><?php echo T_('Random'); ?></th>
            <th class="cel_loop"><?php echo T_('Loop'); ?></th>-->
            <th class="cel_streamtype optional"><?php echo T_('Stream Type'); ?></th>
            <th class="cel_bitrate optional"><?php echo T_('Bitrate'); ?></th>
            <th class="cel_startdate optional"><?php echo T_('Start Date'); ?></th>
            <th class="cel_listeners optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=channel&sort=listeners', T_('Listeners'), 'channel_sort_listeners'); ?></th>
            <th class="cel_streamurl essential"><?php echo T_('Stream URL'); ?></th>
            <th class="cel_state optional"><?php echo T_('State'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $channel_id) {
            $libitem = new Channel($channel_id);
            $libitem->format(); ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="channel_row_<?php echo $libitem->id; ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_channel_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="13"><span class="nodata"><?php echo T_('No channel found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
