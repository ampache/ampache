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

$web_path = AmpConfig::get('web_path'); ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata">
<colgroup>
  <col id="col_action" />
  <col id="col_votes" />
  <col id="col_title" />
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_time" />
  <?php if (Access::check('interface', 100)) { ?>
  <col id="col_admin" />
  <?php
} ?>
</colgroup>
<?php
if (!count($object_ids)) {
    $playlist = new Playlist($democratic->base_playlist); ?>
<tr>
<td><?php echo T_('Playing from base playlist'); ?>.</a></td>
</tr>
<?php
} // if no songs
/* Else we have songs */
else { ?>
<thead>
    <tr class="th-top">
        <th class="cel_action"><?php echo T_('Action'); ?></th>
        <th class="cel_votes"><?php echo T_('Votes'); ?></th>
        <th class="cel_title"><?php echo T_('Title'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if (Access::check('interface', 100)) { ?>
        <th class="cel_admin"><?php echo T_('Admin'); ?></th>
        <?php
    } ?>
    </tr>
</thead>
<tbody>
<?php
$democratic = Democratic::get_current_playlist();
    $democratic->set_parent();
    foreach ($object_ids as $item) {
        if (!is_array($item)) {
            $item = (array) $item;
        }
        $media = new $item['object_type']($item['object_id']);
        $media->format(); ?>
<tr class="<?php echo UI::flip_class(); ?>">
    <td class="cel_action">
    <?php if ($democratic->has_vote($item['object_id'], $item['object_type'])) { ?>
    <?php echo Ajax::button('?page=democratic&action=delete_vote&row_id=' . $item['id'], 'delete', T_('Remove Vote'), 'remove_vote_' . $item['id']); ?>
    <?php
        } else { ?>
    <?php echo Ajax::button('?page=democratic&action=add_vote&object_id=' . $media->id . '&type=' . scrub_out($item['object_type']), 'tick', T_('Add Vote'), 'remove_vote_' . $item['id']); ?>
    <?php
        } ?>
    </td>
    <td class="cel_votes" ><?php echo scrub_out((string) $democratic->get_vote($item['id'])); ?></td>
    <td class="cel_title"><?php echo $media->f_link; ?></td>
    <td class="cel_album"><?php echo $media->f_album_link; ?></td>
    <td class="cel_artist"><?php echo $media->f_artist_link; ?></td>
    <td class="cel_time"><?php echo $media->f_time; ?></td>
    <?php if (Access::check('interface', 100)) { ?>
    <td class="cel_admin">
    <?php echo Ajax::button('?page=democratic&action=delete&row_id=' . $item['id'], 'disable', T_('Delete'), 'delete_row_' . $item['id']); ?>
    </td>
    <?php
        } ?>
</tr>
<?php
    } // end foreach?>
</tbody>
<tfoot>
    <tr class="th-bottom">
        <th class="cel_action"><?php echo T_('Action'); ?></th>
        <th class="cel_votes"><?php echo T_('Votes'); ?></th>
        <th class="cel_title"><?php echo T_('Title'); ?></th>
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_time"><?php echo T_('Time'); ?></th>
        <?php if (Access::check('interface', 100)) { ?>
        <th class="cel_admin"><?php echo T_('Admin'); ?></th>
        <?php
    } ?>
    </tr>
</tfoot>
<?php
} // end else?>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
        require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
    } ?>
