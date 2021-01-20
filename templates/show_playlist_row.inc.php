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
            echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $libitem->id, 'play', T_('Play'), 'play_playlist_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_playlist_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_playlist_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<?php if (AmpConfig::get('playlist_art')) { ?>
<td class="<?php echo $cel_cover; ?>">
    <?php $libitem->display_art(2); ?>
</td>
<?php
    } ?>
<td class="cel_playlist"><?php echo $libitem->f_link ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php
            echo Ajax::button('?action=basket&type=playlist&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'add_playlist_' . $libitem->id);
            if (Access::check('interface', 25)) {
                echo Ajax::button('?action=basket&type=playlist_random&id=' . $libitem->id, 'random', T_('Random to temporary playlist'), 'random_playlist_' . $libitem->id); ?>
            <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'playlist', '<?php echo $libitem->id ?>')">
                <?php echo UI::get_icon('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php
            } ?>
    </span>
</td>
<td class="cel_last_update"><?php echo $libitem->f_last_update ?></td>
<td class="cel_type"><?php echo $libitem->f_type; ?></td>
<td class="cel_medias"><?php echo $libitem->get_media_count(); ?></td>
<td class="cel_owner"><?php echo scrub_out($libitem->f_user); ?></td>
<?php
    if (User::is_registered()) {
        if (AmpConfig::get('ratings')) { ?>
    <td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_playlist"><?php Rating::show($libitem->id, 'playlist'); ?></td>
    <?php
        }
        if (AmpConfig::get('userflags')) { ?>
    <td class="<?php echo $cel_flag; ?>" id="userflag_<?php echo $libitem->id; ?>_playlist"><?php Userflag::show($libitem->id, 'playlist'); ?></td>
    <?php
        }
    } ?>
<td class="cel_action">
<?php
    if (Access::check_function('batch_download') && check_can_zip('playlist')) { ?>
        <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=playlist&amp;id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('batch_download', T_('Batch download')); ?>
        </a>
<?php
    }
    if (Access::check('interface', 25)) {
        if (AmpConfig::get('share')) {
            Share::display_ui('playlist', $libitem->id, false);
        }
    }
    if ($libitem->has_access()) { ?>
    <a id="<?php echo 'edit_playlist_' . $libitem->id ?>" onclick="showEditDialog('playlist_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_playlist_' . $libitem->id ?>', '<?php echo T_('Playlist Edit') ?>', 'playlist_row_')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
    <?php echo Ajax::button('?page=browse&action=delete_object&type=playlist&id=' . $libitem->id, 'delete', T_('Delete'), 'delete_playlist_' . $libitem->id, '', '', T_('Do you really want to delete this Playlist?'));
    } ?>
</td>
