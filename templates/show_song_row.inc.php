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

// Don't show disabled songs to normal users
if ($libitem->enabled || Access::check('interface', 50)) { ?>
<td class="cel_play">
    <span class="cel_play_content">
<?php
    if (isset($argument) && $argument) {
        echo '<b>' . $libitem->f_track . '</b>';
    } ?>
    </span>
    <div class="cel_play_hover">
<?php
    if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $libitem->id, 'play', T_('Play'), 'play_song_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_song_' . $libitem->id);
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_song_' . $libitem->id);
        }
    } ?>
    </div>
</td>
<td class="<?php echo $cel_song; ?>"><?php echo $libitem->f_link ?></td>
<td class="cel_add">
    <span class="cel_item_add">
<?php
    echo Ajax::button('?action=basket&type=song&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'add_' . $libitem->id);
    if (Access::check('interface', 25)) { ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'song', '<?php echo $libitem->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
    <?php
        if (AmpConfig::get('directplay')) {
            echo $libitem->show_custom_play_actions();
        }
    } ?>
    </span>
</td>
<td class="<?php echo $cel_artist; ?>"><?php echo $libitem->f_artist_link ?></td>
<td class="<?php echo $cel_album; ?>"><?php echo $libitem->f_album_link ?></td>
<td class="cel_year"><?php echo $libitem->year ?></td>
<td class="<?php echo $cel_tags; ?>"><?php echo $libitem->f_tags ?></td>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->f_time ?></td>
<?php if (AmpConfig::get('licensing')) { ?>
<td class="<?php echo $cel_license; ?>"><?php echo $libitem->f_license ?></td>
<?php } ?>
<?php if (AmpConfig::get('show_played_times')) { ?>
<td class="<?php echo $cel_counter; ?>"><?php echo $libitem->object_cnt ?></td>
<?php } ?>
<?php if (AmpConfig::get('show_skipped_times')) { ?>
<td class="<?php echo $cel_counter; ?>"><?php echo $libitem->skip_cnt ?></td>
<?php
    }
    if (User::is_registered()) {
        if (AmpConfig::get('ratings')) { ?>
            <td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_song">
                <?php Rating::show($libitem->id, 'song') ?>
            </td>
    <?php
        }
        if (AmpConfig::get('userflags')) { ?>
            <td class="<?php echo $cel_flag; ?>" id="userflag_<?php echo $libitem->id; ?>_song">
                <?php Userflag::show($libitem->id, 'song') ?>
            </td>
    <?php
        }
    } ?>
<td class="cel_action">
    <a href="<?php echo $libitem->link ?>"><?php echo UI::get_icon('preferences', T_('Song Information')) ?></a>
    <?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
        if (AmpConfig::get('sociable')) { ?>
            <a href="<?php echo AmpConfig::get('web_path') ?>/shout.php?action=show_add_shout&type=song&id=<?php echo $libitem->id ?>"><?php echo UI::get_icon('comment', T_('Post Shout')) ?></a>
        <?php
        }
    }
    if (Access::check('interface', 25)) {
        if (AmpConfig::get('share')) {
            Share::display_ui('song', $libitem->id, false);
        }
    }
    if (Access::check_function('download')) { ?>
        <a class="nohtml" href="<?php echo AmpConfig::get('web_path') ?>/stream.php?action=download&song_id=<?php echo $libitem->id ?>"><?php echo UI::get_icon('download', T_('Download')) ?></a>
<?php
    }
    if (Access::check('interface', 50) || ($libitem->user_upload == Core::get_global('user')->id && AmpConfig::get('upload_allow_edit'))) { ?>
        <a id="<?php echo 'edit_song_' . $libitem->id ?>" onclick="showEditDialog('song_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_song_' . $libitem->id ?>', '<?php echo T_('Song Edit') ?>', 'song_')">
            <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
<?php
    }
    if (Access::check('interface', 75) || ($libitem->user_upload == Core::get_global('user')->id && AmpConfig::get('upload_allow_edit'))) {
        $icon                 = $libitem->enabled ? 'disable' : 'enable';
        if ($libitem->enabled) {
            $icon       = 'disable';
            $buttontext = T_('Disable');
        } else {
            $icon       = 'enable';
            $buttontext = T_('Enable');
        }
        $button_flip_state_id = 'button_flip_state_' . $libitem->id; ?>
        <span id="<?php echo $button_flip_state_id; ?>">
            <?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $libitem->id, $icon, $buttontext, 'flip_song_' . $libitem->id); ?>
        </span>
<?php
    }
    if (Catalog::can_remove($libitem)) { ?>
        <a id="<?php echo 'delete_song_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/song.php?action=delete&song_id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('delete', T_('Delete')); ?>
        </a>
<?php
    } ?>
</td>
<?php
    if (Access::check('interface', 50) && isset($argument) && $argument) { ?>
<td class="cel_drag">
    <?php echo UI::get_icon('drag', T_('Reorder')); ?>
</td>
<?php
    }
} ?>
