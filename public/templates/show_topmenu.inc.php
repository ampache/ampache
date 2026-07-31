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

// show_topmenu.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Ui;

/** require@ public/templates/header.inc.php */
/** @var string $web_path */
/** @var string $albumString */
/** @var bool $ui_fixed */
/** @var bool $access25 */
/** @var bool $is_session */
/** @var bool|null $allow_upload */
/** @var string $t_artists */
/** @var string $t_albums */
/** @var string $t_playlists */

$t_home          = T_('Home');
$t_smartlists    = T_('Smartlists');
$t_genres        = T_('Genres');
$t_radioStations = T_('Radio Stations');
$t_radio         = T_('Radio');
$t_favorites     = T_('Favorites');
$t_upload        = T_('Upload');
$t_logout        = T_('Log out'); ?>
            <div id="topmenu_container" class="topmenu_container-<?php echo ($ui_fixed) ? 'fixed' : 'float'; ?>">
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/index.php">
                        <?php echo Ui::get_image('topmenu-home', $t_home); ?>
                        <span><?php echo $t_home; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=album_artist">
                        <?php echo Ui::get_image('topmenu-artist', $t_artists); ?>
                        <span><?php echo $t_artists; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/mashup.php?action=<?php echo $albumString; ?>">
                        <?php echo Ui::get_image('topmenu-album', $t_albums); ?>
                        <span><?php echo $t_albums; ?></span>
                    </a>
                </div>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=playlist">
                        <?php echo Ui::get_image('topmenu-playlist', $t_playlists); ?>
                        <span><?php echo $t_playlists; ?></span>
                    </a>
                </div>
                <?php if (!AmpConfig::get('sidebar_hide_search', false)) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist">
                        <?php echo Ui::get_image('topmenu-smartlist', $t_smartlists); ?>
                        <span><?php echo $t_smartlists; ?></span>
                    </a>
                </div>
                <?php } ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist">
                        <?php echo Ui::get_image('topmenu-tagcloud', $t_genres); ?>
                        <span><?php echo $t_genres; ?></span>
                    </a>
                </div>
                <?php if (AmpConfig::get('live_stream')) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/browse.php?action=live_stream">
                        <?php echo Ui::get_image('topmenu-radio', $t_radioStations); ?>
                        <span><?php echo $t_radio; ?></span>
                    </a>
                </div>
                <?php } ?>

                <?php if (AmpConfig::get('ratings') && $access25) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/stats.php?action=userflag_<?php echo $albumString; ?>">
                        <?php echo Ui::get_image('topmenu-favorite', $t_favorites); ?>
                        <span><?php echo $t_favorites; ?></span>
                    </a>
                </div>
                <?php } ?>
                <?php if ($allow_upload) { ?>
                <div class="topmenu_item">
                    <a href="<?php echo $web_path; ?>/upload.php">
                        <?php echo Ui::get_image('topmenu-upload', $t_upload); ?>
                        <span><?php echo $t_upload; ?></span>
                    </a>
                </div>
                <?php } ?>
                <?php if ($is_session) { ?>
                <div class="topmenu_item">
                    <a target="_top" href="<?php echo $web_path; ?>/logout.php?session=<?php echo Session::get(); ?>" class="nohtml">
                        <?php echo Ui::get_image('topmenu-logout', $t_logout); ?>
                        <span><?php echo $t_logout; ?></span>
                    </a>
                </div>
                <?php } ?>
            </div>
