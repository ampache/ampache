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
?>
<?php $filter_str = (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ?>
<h3 class="box-title"><?php echo T_('Browse Ampache...'); ?></h3>
<table class="tabledata">
<tr id="browse_location">
        <td><?php if ($filter_str !== 'song') {
    ?><a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=song"><?php echo T_('Songs'); ?></a><?php
} else {
        echo T_('Songs');
    } ?></td>
        <td><?php if ($filter_str !== 'album') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=album"><?php echo T_('Albums'); ?></a><?php
    } else {
        echo T_('Albums');
    } ?></td>
        <td><?php if ($filter_str !== 'artist' && $filter_str !== 'album_artist') {
        ?><a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=album_artist"><?php echo T_('Artists'); ?></a><?php
    } else {
        echo T_('Artists');
    } ?></td>
    <?php if (AmpConfig::get('label')) { ?>
        <td><?php if ($filter_str != 'label') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=label"><?php echo T_('Labels'); ?></a><?php
        } else {
            echo T_('Labels');
        } ?>
        </td>
    <?php }
    if (AmpConfig::get('channel')) { ?>
        <td><?php if ($filter_str != 'channel') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=channel"><?php echo T_('Channels'); ?></a><?php
        } else {
            echo T_('Channels');
        } ?>
        </td>
    <?php }
    if (AmpConfig::get('broadcast')) { ?>
        <td><?php if ($filter_str != 'broadcast') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts'); ?></a><?php
        } else {
            echo T_('Broadcasts');
        } ?></td>
    <?php }
    if (AmpConfig::get('live_stream')) { ?>
        <td><?php if ($filter_str != 'live_stream') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations'); ?></a><?php
        } else {
            echo T_('Radio Stations');
        } ?></td>
    <?php }
    if (AmpConfig::get('podcast')) { ?>
        <td><?php if ($filter_str != 'podcast') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=podcast"><?php echo T_('Podcasts'); ?></a><?php
        } else {
            echo T_('Podcasts');
        } ?></td>
    <?php }
    if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
        <td><?php if ($filter_str != 'video') { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/browse.php?action=video"><?php echo T_('Videos'); ?></a><?php
        } else {
            echo T_('Videos');
        } ?></td>
    <?php } ?>
    </tr>
</table>
