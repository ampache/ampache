<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

global $dic;

/** @var User|null $current_user */
$current_user = $current_user ?? Core::get_global('user');
$zipHandler   = $dic->get(ZipHandlerInterface::class);
$batch_dl     = Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD);
$zip_folder   = $batch_dl && $zipHandler->isZipable('folder');
// Title for this folder
$web_path = AmpConfig::get_web_path();

/** @var Folder $folder */
$simple = $folder->get_fullname();
$f_name = $folder->get_fullname();
$title  = ($folder->parent !== null)
        ? $folder->get_f_parent_link() . '&nbsp;\&nbsp;' . scrub_out($f_name)
        : scrub_out($f_name);

$access50          = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$access25          = ($access50 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$show_direct_play  = AmpConfig::get('directplay');
$show_playlist_add = $access25;
$directplay_limit  = AmpConfig::get('direct_play_limit', 500);

if ($directplay_limit > 0) {
    $show_playlist_add = ($folder->object_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
} ?>
<?php Ui::show_box_top($title, 'info-box'); ?>

<div class="item_right_info">
<?php Art::display('folder', $folder->id, scrub_out($f_name), ['width' => 384, 'height' => 384], null, true, false); ?>
</div>
<?php if (User::is_registered() && AmpConfig::get('ratings')) { ?>
    <span id="rating_<?php echo $folder->id; ?>_folder">
        <?php echo Rating::show($folder->id, 'folder', true); ?>
    </span>
    <span id="userflag_<?php echo $folder->id; ?>_folder">
        <?php echo Userflag::show($folder->id, 'folder'); ?>
    </span>
<?php } ?>
<?php if (AmpConfig::get('show_played_times')) { ?>
<br />
<div style="display:inline;">
    <?php echo T_('Played') . ' ' .
/* HINT: Number of times an object has been played */
sprintf(nT_('%d time', '%d times', $folder->total_count), $folder->total_count); ?>
</div>
<?php } ?>

<?php
$owner_id = $folder->get_user_owner();
if (AmpConfig::get('sociable') && !empty($owner_id)) {
    $owner = new User($owner_id); ?>
<div class="item_uploaded_by">
    <?php echo T_('Uploaded by'); ?> <?php echo $owner->get_f_link(); ?>
</div>
<?php
} ?>

<div id="information_actions">
    <h3><?php echo T_('Actions'); ?></h3>
    <ul>
        <?php if ($show_direct_play) {
            $play     = T_('Play');
            $playnext = T_('Play next');
            $playlast = T_('Play last'); ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->id, 'play_circle', $play, 'directplay_full_' . $folder->id); ?>
        </li>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->id . '&playnext=true', 'menu_open', $playnext, 'nextplay_folder_' . $folder->id); ?>
        </li>
            <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->id . '&append=true', 'low_priority', $playlast, 'addplay_folder_' . $folder->id); ?>
        </li>
            <?php } ?>
        <?php
        } ?>

        <?php if ($show_playlist_add) {
            $addtotemp  = T_('Add to Temporary Playlist');
            $randtotemp = T_('Random to Temporary Playlist');
            $addtoexist = T_('Add to playlist'); ?>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=folder&id=' . $folder->id, 'add_circle', $addtotemp, 'play_full_' . $folder->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=folder_random&id=' . $folder->id, 'shuffle', $randtotemp, 'play_random_' . $folder->id); ?>
        </li>
        <li>
            <a id="<?php echo 'add_to_playlist_' . $folder->id; ?>" onclick="showPlaylistDialog(event, 'folder', '<?php echo $folder->id; ?>')">
                <?php echo Ui::get_material_symbol('playlist_add', $addtoexist);
            echo $addtoexist; ?>
            </a>
        </li>
        <?php
        } ?>
        <?php if (AmpConfig::get('use_rss')) { ?>
        <li>
            <?php echo Ui::getRssLink(
                RssFeedTypeEnum::LIBRARY_ITEM,
                $current_user,
                T_('RSS Feed'),
                ['object_type' => 'folder', 'object_id' => (string)$folder->id]
            ); ?>
        </li>
        <?php } ?>
        <?php if (!AmpConfig::get('use_auth') || $access25) { ?>
            <?php if (AmpConfig::get('sociable')) {
                $postshout = "&nbsp;" . T_('Post Shout'); ?>
            <li>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=folder&id=<?php echo $folder->id; ?>">
                    <?php echo Ui::get_material_symbol('comment', $postshout);
                echo $postshout; ?>
                </a>
            </li>
            <?php
            } ?>
        <?php } ?>
    <?php if ($access25 && AmpConfig::get('share')) { ?>
            <li>
                <?php echo Share::display_ui('folder', $folder->id); ?>
            </li>
        <?php } else {
            $link = "&nbsp;" . T_('Link'); ?>
        <li>
            <a href="<?php echo $folder->get_link(); ?>" target=_blank>
                <?php echo Ui::get_material_symbol('open_in_new', $link);
            echo $link; ?>
            </a>
        </li>
    <?php } ?>
        <?php if ((!empty($owner_id) && $owner_id == $current_user?->getId()) || $access50) {
            if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=folder&object_id=<?php echo $folder->id; ?>">
                    <?php echo Ui::get_material_symbol('bar_chart', T_('Graphs'));
                echo T_('Graphs'); ?>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/folders.php?action=update_from_tags&folder=<?php echo $folder->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');">
                <?php echo Ui::get_material_symbol('sync_alt', T_('Update from tags'));
            echo "&nbsp;" . T_('Update from tags'); ?>
            </a>
        </li>
        <?php
        } ?>
        <?php
            if ($zip_folder) {
                $download = "&nbsp;" . T_('Download'); ?>
        <li>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=folder&id=<?php echo $folder->id; ?>" rel="nofollow">
                <?php echo Ui::get_material_symbol('folder_zip', $download); ?>
                <?php echo $download; ?>
            </a>
        </li>
        <?php
            } ?>
        <?php if (Catalog::can_remove($folder)) {
            $delete = T_('Delete'); ?>
        <li>
            <a id="<?php echo 'delete_folder_' . $folder->id; ?>" href="<?php echo $web_path; ?>/folders.php?action=delete&folder_id=<?php echo $folder->id; ?>">
                <?php echo Ui::get_material_symbol('close', $delete); ?>
                <?php echo $delete; ?>
            </a>
        </li>
        <?php
        } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<div id='reordered_list_<?php echo $folder->id; ?>'>
<?php
$folder_items = $folder->get_objects();
$browse       = new Browse();
$browse->set_type('folder');
$browse->set_skip_catalog_check(true);
$browse->add_supplemental_object('folder', $folder);
$browse->set_limit(0);
$browse->set_offset(0);
$browse->set_sort('name', 'ASC', false);
$browse->set_filter('folder', $folder->id);
$browse->show_objects($folder_items);
$browse->store(); ?>
</div>
