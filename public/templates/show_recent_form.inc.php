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
$filter_str      = (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
$showAlbumArtist = AmpConfig::get('show_album_artist');
$showArtist      = AmpConfig::get('show_artist');
$albumString     = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk'; ?>

<h3 class="box-title"><?php echo T_('Recent'); ?></h3>

<div class="category_options">
    <a class="category <?php echo ($filter_str == 'recent_song') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_song"><?php echo T_('Songs'); ?></a>
    <a class="category <?php echo ($filter_str == 'recent_album_disk' || $filter_str == 'recent_album') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_<?php echo $albumString; ?>"><?php echo T_('Albums'); ?></a>
    <?php if ($showArtist || $filter_str == 'recent_artist') { ?>
        <a class="category <?php echo ($filter_str == 'recent_artist') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_artist"><?php echo T_('Artists'); ?></a>
    <?php } ?>
    <?php if ($showAlbumArtist || !$showArtist || $filter_str == 'recent_album_artist') { ?>
        <a class="category <?php echo ($filter_str == 'recent_album_artist') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_album_artist"><?php echo T_('Album Artists'); ?></a>
    <?php } ?>
    <?php if (AmpConfig::get('podcast')) { ?>
        <a class="category <?php echo ($filter_str == 'recent_podcast_episode') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_podcast_episode"><?php echo T_('Podcast Episodes'); ?></a>
    <?php }
    if (AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class)) { ?>
        <a class="category <?php echo ($filter_str == 'recent_video') ? 'current' : '' ?>" href="<?php echo $web_path; ?>/stats.php?action=recent_video"><?php echo T_('Videos'); ?></a>
    <?php } ?>
</div>
