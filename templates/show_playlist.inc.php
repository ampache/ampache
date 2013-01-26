<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
require Config::get('prefix') . '/templates/show_playlist_title.inc.php';
$title = ob_get_contents();
ob_end_clean();
UI::show_box_top('<div id="playlist_row_' . $playlist->id . '">' . $title . 
    '</div>', 'info-box');
?>
<div id="information_actions">
<ul>
    <li>
        <a href="<?php echo Config::get('web_path'); ?>/playlist.php?action=normalize_tracks&amp;playlist_id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('statistics', T_('Normalize Tracks')); ?></a>
        <?php echo T_('Normalize Tracks'); ?>
    </li>
        <?php if (Access::check_function('batch_download')) { ?>
    <li>
        <a href="<?php echo Config::get('web_path'); ?>/batch.php?action=playlist&amp;id=<?php echo $playlist->id; ?>"><?php echo UI::get_icon('batch_download', T_('Batch Download')); ?></a>
        <?php echo T_('Batch Download'); ?>
    </li>
        <?php } ?>
    <li>
        <?php echo Ajax::button('?action=basket&type=playlist&id=' . $playlist->id,'add', T_('Add All'),'play_playlist'); ?>
        <?php echo T_('Add All'); ?>
    </li>
    <li>
        <?php echo Ajax::button('?action=basket&type=playlist_random&id=' . $playlist->id,'random', T_('Add Random'),'play_playlist_random'); ?>
        <?php echo T_('Add Random'); ?>
    </li>
    <?php if ($playlist->has_access()) { ?>
    <li>
        <?php echo Ajax::button('?action=show_edit_object&type=playlist_title&id=' . $playlist->id,'edit', T_('Edit'),'edit_playlist_' . $playlist->id); ?>
        <?php echo T_('Edit'); ?>
    </li>
    <li>
        <a href="<?php echo Config::get('web_path'); ?>/playlist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>">
            <?php echo UI::get_icon('delete'); ?>
        </a>
        <?php echo T_('Delete'); ?>
    </li>
    <?php } ?>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<?php
    $browse = new Browse();
    $browse->set_type('playlist_song');
    $browse->add_supplemental_object('playlist', $playlist->id);
    $browse->set_static_content(true);
    $browse->show_objects($object_ids);
    $browse->store();
?>
