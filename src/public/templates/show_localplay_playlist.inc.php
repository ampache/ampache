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

$localplay = new Localplay(AmpConfig::get('localplay_controller'));
$localplay->connect();
$status = $localplay->status(); ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata">
    <thead>
        <tr class="th-top">
            <th class="cel_track"><?php echo T_('Track'); ?></th>
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $object) {
            $class = ' class="cel_name"';
            if ($status['track'] == $object['track']) {
                $class=' class="cel_name lp_current"';
            } ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="localplay_playlist_<?php echo $object['id']; ?>">
            <td class="cel_track">
                <?php echo scrub_out($object['track']); ?>
            </td>
            <td<?php echo $class; ?>>
                <?php echo $localplay->format_name($object['name'], $object['id']); ?>
            </td>
            <td class="cel_action">
            <?php echo Ajax::button('?page=localplay&action=delete_track&id=' . (int) ($object['id']), 'delete', T_('Delete'), 'localplay_delete_' . (int) ($object['id'])); ?>
            </td>
        </tr>
        <?php
        } if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="3"><span class="error"><?php echo T_('No records found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_track"><?php echo T_('Track'); ?></th>
            <th class="cel_name"><?php echo T_('Name'); ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
