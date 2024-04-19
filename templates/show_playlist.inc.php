<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\RefreshReordered\RefreshPlaylistMediasAction;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

/** @var Playlist $playlist */
/** @var list<int> $object_ids */

ob_start();
echo $playlist->get_fullname();
$title    = ob_get_contents();
$web_path = (string)AmpConfig::get('web_path', '');
ob_end_clean();
Ui::show_box_top('<div id="playlist_row_' . $playlist->id . '">' . $title . '</div>', 'info-box'); ?>
<div class="item_right_info">
<?php $thumb = Ui::is_grid_view('playlist') ? 32 : 11;
$playlist->display_art($thumb, false, false); ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
    <span id="rating_<?php echo $playlist->id; ?>_playlist">
        <?php echo Rating::show($playlist->id, 'playlist'); ?>
    </span>
    <span id="userflag_<?php echo $playlist->id; ?>_playlist">
        <?php echo Userflag::show($playlist->id, 'playlist'); ?>
    </span>
    <?php } ?>
<?php } ?>
<div id="information_actions">
    <ul>
<?php if (Core::get_global('user') instanceof User && (Core::get_global('user')->has_access(AccessLevelEnum::CONTENT_MANAGER) || $playlist->user == Core::get_global('user')->id)) { ?>
        <li>
            <a onclick="submitNewItemsOrder('<?php echo $playlist->id; ?>', 'reorder_playlist_table', 'track_',
                                            '<?php echo $web_path; ?>/playlist.php?action=set_track_numbers&playlist_id=<?php echo $playlist->id; ?>', '<?php echo RefreshPlaylistMediasAction::REQUEST_KEY; ?>')">
                <?php echo Ui::get_material_symbol('save', T_('Save Track Order')); ?>
                <?php echo T_('Save Track Order'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo $web_path; ?>/playlist.php?action=sort_tracks&playlist_id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_material_symbol('sort_by_alpha', T_('Sort Tracks by Artist, Album, Song')); ?>
                <?php echo T_('Sort Tracks by Artist, Album, Song'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo $web_path; ?>/playlist.php?action=remove_duplicates&playlist_id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_material_symbol('tab_close', T_('Remove Duplicates')); ?>
                <?php echo T_('Remove Duplicates'); ?>
            </a>
        </li>
<?php }
global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable('playlist')) { ?>
        <li>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=playlist&id=<?php echo $playlist->id; ?>">
                <?php echo Ui::get_material_symbol('folder_zip', T_('Batch download')); ?>
                <?php echo T_('Batch download'); ?>
            </a>
        </li>
    <?php } ?>
    <?php if (AmpConfig::get('share')) { ?>
        <a onclick="showShareDialog(event, 'playlist', '<?php echo $playlist->id; ?>');">
                <?php echo UI::get_material_symbol('share', T_('Share playlist')); ?>
        &nbsp;&nbsp;<?php echo T_('Share playlist'); ?>
        </a>
    <?php } ?>
    <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id, 'play_circle', T_('Play All'), 'directplay_full_' . $playlist->id); ?>
        </li>
    <?php } ?>
    <?php if (Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&playnext=true', 'menu_open', T_('Play All Next'), 'nextplay_playlist_' . $playlist->id); ?>
        </li>
    <?php } ?>
    <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=playlist&object_id=' . $playlist->id . '&append=true', 'playlist_add', T_('Play All Last'), 'addplay_playlist_' . $playlist->id); ?>
        </li>
    <?php } ?>
        <li>
        <?php echo Ajax::button_with_text('?page=random&action=send_playlist&random_type=playlist&random_id=' . $playlist->id, 'shuffle', T_('Random Play'), 'play_random_' . $playlist->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=playlist&id=' . $playlist->id, 'new_window', T_('Add All to Temporary Playlist'), 'play_playlist'); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=playlist_random&id=' . $playlist->id, 'shuffle', T_('Random All to Temporary Playlist'), 'play_playlist_random'); ?>
        </li>
    <?php if ($playlist->has_access()) { ?>
        <?php $search_id = $playlist->has_search((int)$playlist->user);
        if ($search_id > 0) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/playlist.php?action=refresh_playlist&type=playlist&user_id=<?php echo $playlist->user; ?>&playlist_id=<?php echo $playlist->id; ?>&search_id=<?php echo $search_id; ?>">
                    <?php echo Ui::get_material_symbol('sync_alt'); ?>
                    <?php echo T_('Refresh from Smartlist'); ?>
                </a>
            </li>
        <?php } ?>
        <li>
            <a id="<?php echo 'edit_playlist_' . $playlist->id; ?>" onclick="showEditDialog('playlist_row', '<?php echo $playlist->id; ?>', '<?php echo 'edit_playlist_' . $playlist->id; ?>', '<?php echo addslashes(T_('Playlist Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                <?php echo T_('Edit'); ?>
            </a>
        </li>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/playlist.php?action=delete_playlist&playlist_id=<?php echo $playlist->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to delete this Playlist?'); ?>');">
                <?php echo Ui::get_material_symbol('close'); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
    <?php } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div id='reordered_list_<?php echo $playlist->id; ?>'>
<?php
    $browse = new Browse();
$browse->set_type('playlist_media');
$browse->set_use_filters(false);
$browse->add_supplemental_object('playlist', $playlist->id);
$browse->set_static_content(true);
$browse->duration = Search::get_total_duration($object_ids);
$browse->show_objects($object_ids, true);
$browse->store(); ?>
</div>
