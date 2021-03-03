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

// Don't show disabled medias to normal users
if (!isset($libitem->enabled) || $libitem->enabled || Access::check('interface', 50)) { ?>
<td class="cel_play">
    <span class="cel_play_content"><?php echo '<b>' . $playlist_track . '</b>' ?></span>
    <div class="cel_play_hover">
    <?php
    if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $libitem->id, 'play', T_('Play'), 'play_playlist_' . $object_type . '_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_' . $object_type . '_' . $libitem->id);
        }
    } ?>
    </div>
</td>
<?php if (Art::is_enabled()) { ?>
<td class="<?php echo $cel_cover; ?>">
<div style="max-width: 80px;">
    <?php
    $thumb = (isset($browse) && !$browse->is_grid_view()) ? 11 : 3;
        $libitem->display_art($thumb); ?>
</div>
</td>
<?php
    } ?>
<td class="cel_title"><?php echo $libitem->f_link ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=' . $object_type . '&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'playlist_add_' . $libitem->id);
    if (Access::check('interface', 25)) { ?>
            <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, '<?php echo $object_type ?>', '<?php echo $libitem->id ?>')">
                <?php echo UI::get_icon('playlist_add', T_('Add to playlist')) ?>
            </a>
        <?php
    } ?>
    </span>
</td>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->f_time ?></td>
<?php if (User::is_registered()) {
        if (AmpConfig::get('ratings')) { ?>
    <td class="cel_rating" id="rating_<?php echo $libitem->id ?>_<?php echo $object_type ?>"><?php Rating::show($libitem->id, $object_type) ?></td>
    <?php
        }
        if (AmpConfig::get('userflags')) { ?>
    <td class="<?php echo $cel_flag; ?>" id="userflag_<?php echo $libitem->id ?>_<?php echo $object_type ?>"><?php Userflag::show($libitem->id, $object_type) ?></td>
    <?php
        }
    } ?>
<td class="cel_action">
    <?php if (AmpConfig::get('download')) { ?>
    <a class="nohtml" href="<?php echo AmpConfig::get('web_path') ?>/stream.php?action=download&amp;<?php echo $object_type ?>_id=<?php echo $libitem->id ?>">
        <?php echo UI::get_icon('download', T_('Download')) ?>
    </a>
    <?php
    }
    if (Access::check('interface', 25)) {
        if (AmpConfig::get('share')) {
            Share::display_ui($object_type, $libitem->id, false);
        }
    }
    if ($playlist) {
        if (get_class($playlist) == "Playlist" && $playlist->has_access()) {
            echo Ajax::button('?page=playlist&action=delete_track&playlist_id=' . $playlist->id . '&track_id=' . $object['track_id'], 'delete', T_('Delete'), 'track_del_' . $object['track_id']);
        } ?>
    </td>
    <?php if (Access::check('interface', 50) && get_class($playlist) == "Playlist") { ?>
    <td class="cel_drag">
        <?php echo UI::get_icon('drag', T_('Reorder')) ?>
            </td>
        <?php
        }
    }
} ?>
