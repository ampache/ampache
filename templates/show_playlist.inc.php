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

/**
 * Playlist Box
 * This box is used for actions on the main screen and on a specific playlist page
 * It changes depending on where it is
 */ ?>
<?php
ob_start();
require AmpConfig::get('prefix') . UI::find_template('show_playlist_title.inc.php');
$title = ob_get_contents();
ob_end_clean();
UI::show_box_top('<div id="playlist_row_' . $playlist->id . '">' . $title . '</div>', 'info-box'); ?>
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
    <div style="display:table-cell;" id="rating_<?php echo $playlist->id; ?>_playlist">
            <?php Rating::show($playlist->id, 'playlist'); ?>
    </div>
    <?php
    } ?>
    <?php if (AmpConfig::get('userflags')) { ?>
    <div style="display:table-cell;" id="userflag_<?php echo $playlist->id; ?>_playlist">
            <?php Userflag::show($playlist->id, 'playlist'); ?>
    </div>
    <?php
    } ?>
<?php
} ?>
<div id="information_actions">
    <ul>
    <?php if (Core::get_global('user')->has_access('50') || $playlist->user == Core::get_global('user')->id) { ?>
        <li>
            <a onclick="submitNewItemsOrder('<?php echo $playlist->id; ?>', 'reorder_playlist_table', 'track_',
                                            '<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=set_track_numbers&playlist_id=<?php echo $playlist->id; ?>', 'refresh_playlist_medias')">
                <?php echo UI::get_icon('save', T_('Save Track Order')); ?>
                &nbsp;&nbsp;<?php echo T_('Save Track Order'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=sort_tracks&playlist_id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('sort', T_('Sort Tracks by Artist, Album, Song')); ?>
            &nbsp;&nbsp;<?php echo T_('Sort Tracks by Artist, Album, Song'); ?></a>
        </li>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=remove_duplicates&playlist_id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('wand', T_('Remove Duplicates')); ?>
            &nbsp;&nbsp;<?php echo T_('Remove Duplicates'); ?></a>
        </li>
    <?php
    } ?>
    <?php if (Access::check_function('batch_download') && check_can_zip('playlist')) { ?>
        <li>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=playlist&amp;id=<?php echo $playlist->id; ?>">
                <?php echo UI::get_icon('batch_download', T_('Batch Download')); ?>
                &nbsp;&nbsp;<?php echo T_('Batch Download'); ?>
            </a>
        </li>
    <?php
    } ?>
    <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id, 'play', T_('Play All'), 'directplay_full_' . $playlist->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id, T_('Play All'), 'directplay_full_text_' . $playlist->id); ?>
        </li>
    <?php
    } ?>
    <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&append=true', 'play_add', T_('Play All Last'), 'addplay_playlist_' . $playlist->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&append=true', T_('Play All Last'), 'addplay_playlist_text_' . $playlist->id); ?>
        </li>
    <?php
    } ?>
        <li>
            <?php echo Ajax::button('?action=basket&type=playlist&id=' . $playlist->id, 'add', T_('Add All to Temporary Playlist'), 'play_playlist'); ?>
            <?php echo Ajax::text('?action=basket&type=playlist&id=' . $playlist->id, T_('Add All to Temporary Playlist'), 'play_playlist_text'); ?>
        </li>
        <li>
            <?php echo Ajax::button('?action=basket&type=playlist_random&id=' . $playlist->id, 'random', T_('Random All to Temporary Playlist'), 'play_playlist_random'); ?>
            <?php echo Ajax::text('?action=basket&type=playlist_random&id=' . $playlist->id, T_('Random All to Temporary Playlist'), 'play_playlist_random_text'); ?>
        </li>
    <?php if (Core::get_global('user')->has_access('50') && AmpConfig::get('channel')) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/channel.php?action=show_create&type=playlist&id=<?php echo $playlist->id; ?>">
                <?php echo UI::get_icon('flow'); ?>
                &nbsp;&nbsp;<?php echo T_('Create channel'); ?>
            </a>
        </li>
    <?php
    } ?>
    <?php if ($playlist->has_access()) { ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to delete this Playlist?'); ?>');">
                <?php echo UI::get_icon('delete'); ?>
                &nbsp;&nbsp;<?php echo T_('Delete'); ?>
            </a>
        </li>
    <?php
    } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div id='reordered_list_<?php echo $playlist->id; ?>'>
<?php
    $browse = new Browse();
    $browse->set_type('playlist_media');
    $browse->add_supplemental_object('playlist', $playlist->id);
    $browse->set_static_content(true);
    $browse->duration = Search::get_total_duration($object_ids);
    $browse->show_objects($object_ids, true);
    $browse->store(); ?>
</div>
