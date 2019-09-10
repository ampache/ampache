<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$web_path = AmpConfig::get('web_path');
?>

<ul id="sidebar-light">
    <li><a href="<?php echo $web_path ?>/mashup.php?action=artist"><?php echo UI::get_image('topmenu-artist', T_('Artists')); ?><br /><?php echo T_('Artists') ?></a></li>
    <li><a href="<?php echo $web_path ?>/mashup.php?action=album"><?php echo UI::get_image('topmenu-album', T_('Albums')); ?><br /><?php echo T_('Albums') ?></a></li>
    <li><a href="<?php echo $web_path ?>/mashup.php?action=playlist"><?php echo UI::get_image('topmenu-playlist', T_('Playlists')); ?><br /><?php echo T_('Playlists') ?></a></li>
    <li><a href="<?php echo $web_path ?>/browse.php?action=smartplaylist"><?php echo UI::get_image('topmenu-playlist', T_('Smartlists')); ?><br /><?php echo T_('Smartlists') ?></a></li>
    <li><a href="<?php echo $web_path ?>/browse.php?action=tag"><?php echo UI::get_image('topmenu-tagcloud', T_('Tag Cloud')); ?><br /><?php echo T_('Tag Cloud') ?></a></li>
    <?php if (AmpConfig::get('live_stream')) {
    ?>
    <li><a href="<?php echo $web_path ?>/browse.php?action=live_stream"><?php echo UI::get_image('topmenu-radio', T_('Radio Stations')); ?><br /><?php echo T_('Radio') ?></a></li>
    <?php
} ?>
    <?php if (AmpConfig::get('userflags') && Access::check('interface', 25)) {
        ?>
    <li><a href="<?php echo $web_path ?>/stats.php?action=userflag"><?php echo UI::get_image('topmenu-favorite', T_('Favorites')); ?><br /><?php echo T_('Favorites') ?></a></li>
    <?php
    } ?>
    <?php if (AmpConfig::get('allow_upload') && Access::check('interface', 25)) {
        ?>
    <li><a href="<?php echo $web_path ?>/upload.php"><?php echo UI::get_image('topmenu-upload', T_('Upload')); ?><br /><?php echo T_('Upload') ?></a></li>
    <?php
    } ?>
</ul>
