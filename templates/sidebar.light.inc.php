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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\User;

/** require@ public/templates/header.inc.php */
/** @var string $web_path */
/** @var string $t_artists */
/** @var string $t_albums */
/** @var string $t_playlists */
/** @var string $t_smartlists */
/** @var string $t_genres */
/** @var string $t_radioStations */
/** @var string $t_radio */
/** @var string $t_favorites */
/** @var string $t_upload */
/** @var string $t_logout */
/** @var bool $access25 */
/** @var bool|null $allow_upload */

$current_user = $current_user ?? Core::get_global('user');
$is_session   = (User::is_registered() && !empty($current_user) && ($current_user->id ?? 0) > 0);
$allow_upload = $allow_upload ?? $access25 && Upload::can_upload($current_user);
$albumString  = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk'; ?>
<ul id="sidebar-light">
    <li><a href="<?php echo $web_path; ?>/mashup.php?action=artist"><?php echo Ui::get_image('topmenu-artist', $t_artists); ?><br /><?php echo $t_artists; ?></a></li>
    <li><a href="<?php echo $web_path; ?>/mashup.php?action=album"><?php echo Ui::get_image('topmenu-album', $t_albums); ?><br /><?php echo $t_albums; ?></a></li>
    <li><a href="<?php echo $web_path; ?>/mashup.php?action=playlist"><?php echo Ui::get_image('topmenu-playlist', $t_playlists); ?><br /><?php echo $t_playlists; ?></a></li>
    <li><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo Ui::get_image('topmenu-playlist', $t_smartlists); ?><br /><?php echo $t_smartlists; ?></a></li>
    <li><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist"><?php echo Ui::get_image('topmenu-tagcloud', $t_genres); ?><br /><?php echo $t_genres; ?></a></li>
    <?php if (AmpConfig::get('live_stream')) { ?>
    <li><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo Ui::get_image('topmenu-radio', $t_radioStations); ?><br /><?php echo $t_radio; ?></a></li>
    <?php } ?>
    <?php if (AmpConfig::get('ratings') && $access25) { ?>
    <li><a href="<?php echo $web_path; ?>/stats.php?action=userflag_<?php echo $albumString; ?>"><?php echo Ui::get_image('topmenu-favorite', $t_favorites); ?><br /><?php echo $t_favorites; ?></a></li>
    <?php } ?>
    <?php if ($allow_upload) { ?>
    <li><a href="<?php echo $web_path; ?>/upload.php"><?php echo Ui::get_image('topmenu-upload', $t_upload); ?><br /><?php echo $t_upload; ?></a></li>
    <?php } ?>
    <?php if ($is_session) { ?>
        <li><a target="_top" href="<?php echo $web_path; ?>/logout.php?session=<?php echo Session::get(); ?>" class="nohtml"><img src="<?php echo $web_path; ?>/images/topmenu-logout.png" title="<?php echo $t_logout; ?>" /><br /><?php echo $t_logout; ?></a></li>
    <?php } ?>
</ul>
