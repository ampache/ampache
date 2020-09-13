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
            echo Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $libitem->id, 'play', T_('Play'), 'play_playlist_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=search&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_playlist_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<td class="cel_playlist"><?php echo $libitem->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=search&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'add_playlist_' . $libitem->id); ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'search', '<?php echo $libitem->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_type"><?php echo $libitem->f_type; ?></td>
<td class="cel_random"><?php echo($libitem->random ? T_('Yes') : T_('No')); ?></td>
<td class="cel_limit"><?php echo(($libitem->limit > 0) ? $libitem->limit : T_('None')); ?></td>
<td class="cel_owner"><?php echo scrub_out($libitem->f_user); ?></td>
<td class="cel_action">
        <?php
            if (Access::check_function('batch_download') && check_can_zip('search')) { ?>
                <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=search&amp;id=<?php echo $libitem->id; ?>">
                    <?php echo UI::get_icon('batch_download', T_('Batch download')); ?>
                </a>
        <?php
            }
            if ($libitem->has_access()) { ?>
                <a id="<?php echo 'edit_playlist_' . $libitem->id ?>" onclick="showEditDialog('search_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_playlist_' . $libitem->id ?>', '<?php echo T_('Smart Playlist Edit') ?>', 'smartplaylist_row_')">
                    <?php echo UI::get_icon('edit', T_('Edit')); ?>
                </a>
                <?php
                echo Ajax::button('?page=browse&action=delete_object&type=smartplaylist&id=' . $libitem->id, 'delete', T_('Delete'), 'delete_playlist_' . $libitem->id);
            } ?>
</td>
