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
        if ($show_direct_play) {
            echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id, 'play', T_('Play'), 'play_artist_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_artist_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=artist&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_artist_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<?php
if (Art::is_enabled()) {
            $name = scrub_out($libitem->full_name); ?>
<td class="<?php echo $cel_cover; ?>">
    <?php
    $thumb = (isset($browse) && !$browse->is_grid_view()) ? 11 : 1;
            Art::display('artist', $libitem->id, $name, $thumb, AmpConfig::get('web_path') . '/artists.php?action=show&artist=' . $libitem->id); ?>
</td>
<?php
        } ?>
<td class="<?php echo $cel_artist; ?>"><?php echo $libitem->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
    <?php
        if ($show_playlist_add) {
            echo Ajax::button('?action=basket&type=artist&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'add_artist_' . $libitem->id);
            echo Ajax::button('?action=basket&type=artist_random&id=' . $libitem->id, 'random', T_('Random to temporary playlist'), 'random_artist_' . $libitem->id); ?>
            <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'artist', '<?php echo $libitem->id ?>')">
                <?php echo UI::get_icon('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php
        } ?>
    </span>
</td>
<td class="cel_songs optional"><?php echo $libitem->songs; ?></td>
<td class="cel_albums optional"><?php echo $libitem->albums; ?></td>
<td class="<?php echo $cel_time; ?> optional"><?php echo $libitem->f_time; ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->object_cnt; ?></td>
<?php
        } ?>
<td class="<?php echo $cel_tags; ?>"><?php echo $libitem->f_tags; ?></td>
<?php
    if (User::is_registered()) {
        if (AmpConfig::get('ratings')) { ?>
            <td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_artist"><?php Rating::show($libitem->id, 'artist'); ?></td>
        <?php
        }
        if (AmpConfig::get('userflags')) { ?>
            <td class="<?php echo $cel_flag; ?>" id="userflag_<?php echo $libitem->id; ?>_artist"><?php Userflag::show($libitem->id, 'artist'); ?></td>
        <?php
        }
    } ?>
<td class="cel_action">
<?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) {
        if (AmpConfig::get('sociable') && (!$libitem->allow_group_disks || ($libitem->allow_group_disks && count($libitem->album_suite) <= 1))) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=artist&amp;id=<?php echo $libitem->id; ?>">
        <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
    </a>
    <?php
    }
        if ($libitem->can_edit()) { ?>
        <a id="<?php echo 'edit_artist_' . $libitem->id ?>" onclick="showEditDialog('artist_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_artist_' . $libitem->id ?>', '<?php echo T_('Artist Edit') ?>', 'artist_')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
    <?php
    }
        if (Catalog::can_remove($libitem)) { ?>
        <a id="<?php echo 'delete_artist_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/artists.php?action=delete&artist_id=<?php echo $libitem->id; ?>">
            <?php echo UI::get_icon('delete', T_('Delete')); ?>
        </a>
    <?php
    }
    } ?>
</td>
