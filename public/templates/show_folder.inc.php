<?php

declare(strict_types=1);

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

// show_folder.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\User;

/** @var BrowseFactoryInterface $browseFactory */
/** @var string $browseForm */
/** @var ZipHandlerInterface $zipHandler */
/** @var User|null $current_user */
$current_user = $current_user ?? Core::get_global('user');
$batch_dl     = Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD);
$zip_folder   = $batch_dl && $zipHandler->isZipable('folder');
// Title for this folder
$web_path = AmpConfig::get_web_path();

/** @var Folder $folder */
$simple = $folder->get_fullname();
$f_name = $folder->get_fullname();
if ($folder->getId() === -1) {
    $title = scrub_out($f_name);
} else {
    $title = ($folder->parent !== null)
        ? $folder->get_f_parent_link() . '&nbsp;' . '\\' . '&nbsp;' . scrub_out($f_name)
        : $folder->get_f_home_link() . '&nbsp;' . '\\' . '&nbsp;' . scrub_out($f_name);
}
$access50          = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$access25          = ($access50 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$show_direct_play  = (bool) AmpConfig::get('directplay');
$show_playlist_add = $access25;
$directplay_limit  = AmpConfig::get('direct_play_limit', 500);
// Every action here queues what sits below the folder, subfolders included, so that is what decides playability
$media_count = ($folder->getId() > 0)
    ? $folder->get_media_count()
    : 0;

if ($directplay_limit > 0) {
    $show_playlist_add = $show_playlist_add && ($media_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
}

$show_direct_play  = $show_direct_play && $media_count > 0;
$show_playlist_add = $show_playlist_add && $media_count > 0; ?>
<?php echo $browseForm; ?>
<?php Ui::show_box_top($title, 'info-box'); ?>

<div class="item_right_info">
</div>

<?php // the root listing is a virtual folder with nothing to act on, so the panel is omitted rather than drawn empty
if ($show_direct_play || $show_playlist_add) { ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?></h3>
    <ul>
<?php if ($show_direct_play) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->getId(), 'play_circle', T_('Play'), 'directplay_full_' . $folder->getId()); ?>
        </li>
<?php if (Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->getId() . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_folder_' . $folder->getId()); ?>
        </li>
<?php }
if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=folder&object_id=' . $folder->getId() . '&append=true', 'low_priority', T_('Play last'), 'addplay_folder_' . $folder->getId()); ?>
        </li>
<?php }
}
    if ($show_playlist_add) {
        $addtoexist = Ui::get_add_to_list_label(); ?>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=folder&id=' . $folder->getId(), 'new_window', T_('Add to Temporary Playlist'), 'play_full_' . $folder->getId()); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=folder_random&id=' . $folder->getId(), 'shuffle', T_('Random to Temporary Playlist'), 'play_random_' . $folder->getId()); ?>
        </li>
        <li>
            <a id="<?php echo 'add_to_playlist_' . $folder->getId(); ?>" onclick="showPlaylistDialog(event, 'folder', '<?php echo $folder->getId(); ?>')">
                <?php echo Ui::get_material_symbol('playlist_add', $addtoexist); ?>
                <?php echo $addtoexist; ?>
            </a>
        </li>
<?php } ?>
    </ul>
</div>
<?php } ?>
<?php Ui::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<div id='reordered_list_<?php echo $folder->id; ?>'>
<?php
$browse = $browseFactory->create();
$browse->set_type('folder');
$browse->set_use_pages(true);
$browse->set_simple_browse(true);
$browse->set_skip_catalog_check($folder->id !== -1);
$browse->add_supplemental_object('folder', $folder);
$browse->set_sort('name', 'ASC', false);
$browse->set_filter('int_id', $folder->id);
$browse->show_objects();
$browse->store(); ?>
</div>
