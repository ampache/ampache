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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Util\Ui;

?>

<ul id="sidebar-light">
    <li><a href="<?php echo $web_path ?>/mashup.php?action=artist"><?php echo Ui::get_image('topmenu-artist', $t_artists); ?><br /><?php echo $t_artists ?></a></li>
    <li><a href="<?php echo $web_path ?>/mashup.php?action=album"><?php echo Ui::get_image('topmenu-album', $t_albums); ?><br /><?php echo $t_albums ?></a></li>
    <li><a href="<?php echo $web_path ?>/mashup.php?action=playlist"><?php echo Ui::get_image('topmenu-playlist', $t_playlists); ?><br /><?php echo $t_playlists ?></a></li>
    <li><a href="<?php echo $web_path ?>/browse.php?action=smartplaylist"><?php echo Ui::get_image('topmenu-playlist', $t_smartlists); ?><br /><?php echo $t_smartlists ?></a></li>
    <li><a href="<?php echo $web_path ?>/browse.php?action=tag&type=song"><?php echo Ui::get_image('topmenu-tagcloud', $t_genres); ?><br /><?php echo $t_genres ?></a></li>
    <?php if (AmpConfig::get('live_stream')) { ?>
    <li><a href="<?php echo $web_path ?>/browse.php?action=live_stream"><?php echo Ui::get_image('topmenu-radio', $t_radioStations); ?><br /><?php echo $t_radio ?></a></li>
    <?php
} ?>
    <?php if (AmpConfig::get('userflags') && Access::check('interface', 25)) { ?>
    <li><a href="<?php echo $web_path ?>/stats.php?action=userflag"><?php echo Ui::get_image('topmenu-favorite', $t_favorites); ?><br /><?php echo $t_favorites ?></a></li>
    <?php
    } ?>
    <?php if (AmpConfig::get('allow_upload') && Access::check('interface', 25)) { ?>
    <li><a href="<?php echo $web_path ?>/upload.php"><?php echo Ui::get_image('topmenu-upload', $t_upload); ?><br /><?php echo $t_upload ?></a></li>
    <?php
    } ?>
    <li><a target="_top" href="<?php echo $web_path; ?>/logout.php" class="nohtml"><img src="<?php echo $web_path ?>/images/topmenu-logout.png" title="<?php echo $t_logout ?>" /><br /><?php echo $t_logout ?></a></li>
</ul>
