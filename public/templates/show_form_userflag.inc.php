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
use Ampache\Module\Util\Ui;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

/** @var bool $by_user */

$web_path = AmpConfig::get_web_path();

$videoRepository = $dic->get(VideoRepositoryInterface::class);
$filter_str      = (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
$showAlbumArtist = AmpConfig::get('show_album_artist');
$showArtist      = AmpConfig::get('show_artist');
$albumString     = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk'; ?>

<h3 class="box-title"><?php echo T_('Favorites'); ?></h3>

<div class="category_options">
    <a class="category <?php echo ($filter_str == 'userflag_song') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_song"><?php echo T_('Songs'); ?></a>
    <a class="category <?php echo ($filter_str == 'userflag_album_disk' || $filter_str == 'userflag_album') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_<?php echo $albumString; ?>"><?php echo T_('Albums'); ?></a>
    <?php if ($showArtist || $filter_str == 'userflag_artist') { ?>
        <a class="category <?php echo ($filter_str == 'userflag_artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_artist"><?php echo T_('Artists'); ?></a>
    <?php } ?>
    <?php if ($showAlbumArtist || !$showArtist || $filter_str == 'userflag_album_artist') { ?>
        <a class="category <?php echo ($filter_str == 'userflag_album_artist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_album_artist"><?php echo T_('Album Artists'); ?></a>
    <?php } ?>
    <?php if (AmpConfig::get('podcast')) { ?>
        <a class="category <?php echo ($filter_str == 'userflag_podcast_episode') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_podcast_episode"><?php echo T_('Podcast Episodes'); ?></a>
    <?php }
    if (AmpConfig::get('allow_video') && $videoRepository->getItemCount()) { ?>
        <a class="category <?php echo ($filter_str == 'userflag_video') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_video"><?php echo T_('Videos'); ?></a>
    <?php } ?>
    <a class="category <?php echo ($filter_str == 'userflag_playlist') ? 'current' : ''; ?>" href="<?php echo $web_path; ?>/stats.php?action=userflag_playlist"><?php echo T_('Playlists'); ?></a>
</div>
<div id="information_actions">
    <ul>
        <li>
        <?php if ($by_user) { ?>
            <a href="<?php echo $web_path; ?>/stats.php?action=<?php echo $filter_str; ?>&by_user=0">
                <?php echo Ui::get_material_symbol('group', T_('Show All')); ?>
                <?php echo T_('Show All'); ?>
            </a>
        <?php } else { ?>
            <a href="<?php echo $web_path; ?>/stats.php?action=<?php echo $filter_str; ?>&by_user=1">
                <?php echo Ui::get_material_symbol('person', T_('Personal')); ?>
                <?php echo T_('Personal'); ?>
            </a>
        <?php } ?>
</div>
