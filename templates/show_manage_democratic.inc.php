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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

Ui::show_box_top(T_('Manage'));  ?>
<table class="tabledata striped-rows">
    <tr class="th-top">
        <th class="cel_number"><?php echo T_('Playlist'); ?></th>
        <th class="cel_base_playlist"><?php echo T_('Base Playlist'); ?></th>
        <th class="cel_cooldown"><?php echo T_('Cooldown'); ?></th>
        <th class="cel_level"><?php echo T_('Level'); ?></th>
        <th class="cel_default"><?php echo T_('Default'); ?></th>
        <th class="cel_vote_count"><?php echo T_('Songs'); ?></th>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    </tr>
    <?php
        foreach ($playlists as $democratic_id) {
            $democratic = new Democratic($democratic_id);
            $democratic->format();
            $playlist = new Playlist($democratic->base_playlist);
            $playlist->format(); ?>
    <tr>
        <td><?php echo scrub_out($democratic->name); ?></td>
        <td><?php echo $playlist->f_link; ?></td>
        <td><?php echo $democratic->f_cooldown; ?></td>
        <td><?php echo $democratic->f_level; ?></td>
        <td><?php echo $democratic->f_primary; ?></td>
        <td><?php echo $democratic->count_items(); ?></td>
        <td>
        <?php echo Ajax::button('?page=democratic&action=send_playlist&democratic_id=' . $democratic->id, 'all', T_('Play'), 'play_democratic'); ?>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/democratic.php?action=delete&amp;democratic_id=<?php echo scrub_out($democratic->id); ?>"><?php echo Ui::get_icon('delete', T_('Delete')); ?></a>
        </td>
    </tr>
    <?php
        } if (!count($playlists)) { ?>
    <tr>
        <td colspan="10"><span class="nodata"><?php echo T_('No democratic found'); ?></span></td>
    </tr>
<?php
        } ?>
</table>
<br />
<div>
    <a class="button" href="<?php echo AmpConfig::get('web_path'); ?>/democratic.php?action=show_create"><?php echo T_('Create Playlist'); ?></a>
</div>
<?php Ui::show_box_bottom(); ?>
