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
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay')) {
            echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id, 'play', T_('Play live stream'), 'play_live_stream_' . $libitem->id);
        } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
    <?php
    $thumb = (isset($browse) && !$browse->is_grid_view()) ? 11 : 1;
    $libitem->display_art($thumb); ?>
</td>
<td class="cel_streamname"><?php echo $libitem->f_link; ?></td>
<td class="cel_streamurl"><?php echo $libitem->f_url_link; ?></td>
<td class="cel_codec"><?php echo $libitem->codec; ?></td>
<td class="cel_action">
    <?php
        if (Access::check('interface', 50)) { ?>
        <a id="<?php echo 'edit_live_stream_' . $libitem->id ?>" onclick="showEditDialog('live_stream_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_live_stream_' . $libitem->id ?>', '<?php echo T_('Live Stream Edit') ?>', 'live_stream_')">
            <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php
        }
        if (Access::check('interface', 75)) {
            echo Ajax::button('?page=browse&action=delete_object&type=live_stream&id=' . $libitem->id, 'delete', T_('Delete'), 'delete_live_stream_' . $libitem->id);
        } ?>
</td>
