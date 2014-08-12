<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Playlist Box
 * This box is used for actions on the main screen and on a specific playlist page
 * It changes depending on where it is
 */
?>
<?php
ob_start();
require AmpConfig::get('prefix') . '/templates/show_playlist_title.inc.php';
$title = ob_get_contents();
ob_end_clean();
UI::show_box_top('<div id="playlist_row_' . $playlist->id . '">' . $title . '</div>', 'info-box');
?>
<div id="information_actions">
    <ul>
        <li>
            <a onclick="submitNewItemsOrder('<?php echo $playlist->id; ?>', 'reorder_playlist_table', 'track_',
                                            '<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=set_track_numbers&playlist_id=<?php echo $playlist->id; ?>', 'refresh_playlist_songs')">
                <?php echo UI::get_icon('save', T_('Save Tracks Order')); ?>
                &nbsp;&nbsp;<?php echo T_('Save Tracks Order'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=sort_tracks&amp;playlist_id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('statistics',_('Sort Tracks by Artist, Album, Song')); ?>
            &nbsp;&nbsp;<?php echo T_('Sort Tracks by Artist, Album, Song'); ?></a>
        </li>
    <?php if (Access::check_function('batch_download')) { ?>
        <li>
            <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=playlist&amp;id=<?php echo $playlist->id; ?>">
                <?php echo UI::get_icon('batch_download', T_('Batch Download')); ?>
                &nbsp;&nbsp;<?php echo T_('Batch Download'); ?>
            </a>
        </li>
    <?php } ?>
    <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id,'play', T_('Play all'),'directplay_full_' . $playlist->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id, T_('Play all'),'directplay_full_text_' . $playlist->id); ?>
        </li>
    <?php } ?>
    <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&append=true','play_add', T_('Play all last'),'addplay_playlist_' . $playlist->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&append=true', T_('Play all last'),'addplay_playlist_text_' . $playlist->id); ?>
        </li>
    <?php } ?>
        <li>
            <?php echo Ajax::button('?action=basket&type=playlist&id=' . $playlist->id,'add', T_('Add all to temporary playlist'),'play_playlist'); ?>
            <?php echo Ajax::text('?action=basket&type=playlist&id=' . $playlist->id, T_('Add all to temporary playlist'),'play_playlist_text'); ?>
        </li>
        <li>
            <?php echo Ajax::button('?action=basket&type=playlist_random&id=' . $playlist->id,'random', T_('Random all to temporary playlist'),'play_playlist_random'); ?>
            <?php echo Ajax::text('?action=basket&type=playlist_random&id=' . $playlist->id, T_('Random all to temporary playlist'),'play_playlist_random_text'); ?>
        </li>
    <?php if ($GLOBALS['user']->has_access('50')) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/channel.php?action=show_create&type=playlist&id=<?php echo $playlist->id; ?>">
                <?php echo UI::get_icon('flow'); ?>
                &nbsp;&nbsp;<?php echo T_('Create channel'); ?>
            </a>
        </li>
    <?php } ?>
    <?php if ($playlist->has_access()) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/playlist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>" onclick="return confirm('<?php echo T_('Do you really want to delete the playlist?'); ?>');">
                <?php echo UI::get_icon('delete'); ?>
                &nbsp;&nbsp;<?php echo T_('Delete'); ?>
            </a>
        </li>
    <?php } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div id='reordered_list_<?php echo $playlist->id; ?>'>
<?php
    $browse = new Browse();
    $browse->set_type('playlist_song');
    $browse->add_supplemental_object('playlist', $playlist->id);
    $browse->set_static_content(false);
    $browse->show_objects($object_ids);
    $browse->store();
?>
</div>
