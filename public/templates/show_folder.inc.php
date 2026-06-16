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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Rating;
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
        ? $folder->get_f_parent_link() . '&nbsp;' . '\\' . '&nbsp;' . scrub_out($f_name)
        : $folder->get_f_home_link() . '&nbsp;' . '\\' . '&nbsp;' . scrub_out($f_name);

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

<div id="information_actions">
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
