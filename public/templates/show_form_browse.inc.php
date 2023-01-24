<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\Video;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$web_path        = AmpConfig::get('web_path');
$filter_str      = (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$showAlbumArtist = AmpConfig::get('show_album_artist');
$showArtist      = AmpConfig::get('show_artist');
$albumString     = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk'; ?>

<h3 class="box-title"><?php echo T_('Browse Ampache...'); ?></h3>

<div class="category_options">
    <a class="category <?php echo ($filter_str == 'song') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo T_('Songs'); ?></a>
    <a class="category <?php echo ($filter_str == 'album_disk' || $filter_str == 'album') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=<?php echo $albumString; ?>"><?php echo T_('Albums'); ?></a>
    <?php if ($showArtist || $filter_str == 'artist') { ?>
        <a class="category <?php echo ($filter_str == 'artist') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo T_('Artists'); ?></a>
    <?php } ?>
    <?php if ($showAlbumArtist || !$showArtist || $filter_str == 'album_artist') { ?>
        <a class="category <?php echo ($filter_str == 'album_artist') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=album_artist"><?php echo T_('Album Artists'); ?></a>
    <?php } ?>
    <?php if (AmpConfig::get('label')) { ?>
        <a class="category <?php echo ($filter_str == 'label') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=label"><?php echo T_('Labels'); ?></a>
    <?php }
    if (AmpConfig::get('broadcast')) { ?>
        <a class="category <?php echo ($filter_str == 'broadcast') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts'); ?></a>
    <?php }
    if (AmpConfig::get('live_stream')) { ?>
        <a class="category <?php echo ($filter_str == 'live_stream') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations'); ?></a>
    <?php }
    if (AmpConfig::get('podcast')) { ?>
        <a class="category <?php echo ($filter_str == 'podcast') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=podcast"><?php echo T_('Podcasts'); ?></a>
        <a class="category <?php echo ($filter_str == 'podcast_episode') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=podcast_episode"><?php echo T_('Podcast Episodes'); ?></a>
    <?php }
    if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($filter_str == 'video') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/browse.php?action=video"><?php echo T_('Videos'); ?></a>
    <?php } ?>
</div>
