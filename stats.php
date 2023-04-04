<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=1);

use Ampache\Module\Application\ApplicationRunner;
use Ampache\Module\Application\Stats\GraphAction;
use Ampache\Module\Application\Stats\HighestAlbumAction;
use Ampache\Module\Application\Stats\HighestAlbumArtistAction;
use Ampache\Module\Application\Stats\HighestAlbumDiskAction;
use Ampache\Module\Application\Stats\HighestArtistAction;
use Ampache\Module\Application\Stats\HighestPlaylistAction;
use Ampache\Module\Application\Stats\HighestPodcastEpisodeAction;
use Ampache\Module\Application\Stats\HighestSongAction;
use Ampache\Module\Application\Stats\HighestVideoAction;
use Ampache\Module\Application\Stats\NewestAlbumAction;
use Ampache\Module\Application\Stats\NewestAlbumDiskAction;
use Ampache\Module\Application\Stats\NewestAlbumArtistAction;
use Ampache\Module\Application\Stats\NewestArtistAction;
use Ampache\Module\Application\Stats\NewestPlaylistAction;
use Ampache\Module\Application\Stats\NewestPodcastEpisodeAction;
use Ampache\Module\Application\Stats\NewestSongAction;
use Ampache\Module\Application\Stats\NewestVideoAction;
use Ampache\Module\Application\Stats\PopularAlbumAction;
use Ampache\Module\Application\Stats\PopularAlbumArtistAction;
use Ampache\Module\Application\Stats\PopularAlbumDiskAction;
use Ampache\Module\Application\Stats\PopularArtistAction;
use Ampache\Module\Application\Stats\PopularPlaylistAction;
use Ampache\Module\Application\Stats\PopularPodcastEpisodeAction;
use Ampache\Module\Application\Stats\PopularSongAction;
use Ampache\Module\Application\Stats\PopularVideoAction;
use Ampache\Module\Application\Stats\RecentAlbumAction;
use Ampache\Module\Application\Stats\RecentAlbumArtistAction;
use Ampache\Module\Application\Stats\RecentAlbumDiskAction;
use Ampache\Module\Application\Stats\RecentArtistAction;
use Ampache\Module\Application\Stats\RecentPlaylistAction;
use Ampache\Module\Application\Stats\RecentPodcastEpisodeAction;
use Ampache\Module\Application\Stats\RecentSongAction;
use Ampache\Module\Application\Stats\RecentVideoAction;
use Ampache\Module\Application\Stats\ShareAction;
use Ampache\Module\Application\Stats\ShowAction;
use Ampache\Module\Application\Stats\ShowUserAction;
use Ampache\Module\Application\Stats\UploadAction;
use Ampache\Module\Application\Stats\UserflagAlbumAction;
use Ampache\Module\Application\Stats\UserflagAlbumArtistAction;
use Ampache\Module\Application\Stats\UserflagAlbumDiskAction;
use Ampache\Module\Application\Stats\UserflagArtistAction;
use Ampache\Module\Application\Stats\UserflagPlaylistAction;
use Ampache\Module\Application\Stats\UserflagPodcastEpisodeAction;
use Ampache\Module\Application\Stats\UserflagSongAction;
use Ampache\Module\Application\Stats\UserflagVideoAction;
use Ampache\Module\Application\Stats\WantedAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        ShowUserAction::REQUEST_KEY => ShowUserAction::class,
        NewestAlbumAction::REQUEST_KEY => NewestAlbumAction::class,
        NewestAlbumDiskAction::REQUEST_KEY => NewestAlbumDiskAction::class,
        NewestAlbumArtistAction::REQUEST_KEY => NewestAlbumArtistAction::class,
        NewestArtistAction::REQUEST_KEY => NewestArtistAction::class,
        NewestPlaylistAction::REQUEST_KEY => NewestPlaylistAction::class,
        NewestPodcastEpisodeAction::REQUEST_KEY => NewestPodcastEpisodeAction::class,
        NewestSongAction::REQUEST_KEY => NewestSongAction::class,
        NewestVideoAction::REQUEST_KEY => NewestVideoAction::class,
        PopularAlbumAction::REQUEST_KEY => PopularAlbumAction::class,
        PopularAlbumArtistAction::REQUEST_KEY => PopularAlbumArtistAction::class,
        PopularAlbumDiskAction::REQUEST_KEY => PopularAlbumDiskAction::class,
        PopularArtistAction::REQUEST_KEY => PopularArtistAction::class,
        PopularPlaylistAction::REQUEST_KEY => PopularPlaylistAction::class,
        PopularPodcastEpisodeAction::REQUEST_KEY => PopularPodcastEpisodeAction::class,
        PopularSongAction::REQUEST_KEY => PopularSongAction::class,
        PopularVideoAction::REQUEST_KEY => PopularVideoAction::class,
        HighestAlbumAction::REQUEST_KEY => HighestAlbumAction::class,
        HighestAlbumArtistAction::REQUEST_KEY => HighestAlbumArtistAction::class,
        HighestAlbumDiskAction::REQUEST_KEY => HighestAlbumDiskAction::class,
        HighestArtistAction::REQUEST_KEY => HighestArtistAction::class,
        HighestPlaylistAction::REQUEST_KEY => HighestPlaylistAction::class,
        HighestPodcastEpisodeAction::REQUEST_KEY => HighestPodcastEpisodeAction::class,
        HighestSongAction::REQUEST_KEY => HighestSongAction::class,
        HighestVideoAction::REQUEST_KEY => HighestVideoAction::class,
        UserflagAlbumAction::REQUEST_KEY => UserflagAlbumAction::class,
        UserflagAlbumArtistAction::REQUEST_KEY => UserflagAlbumArtistAction::class,
        UserflagAlbumDiskAction::REQUEST_KEY => UserflagAlbumDiskAction::class,
        UserflagArtistAction::REQUEST_KEY => UserflagArtistAction::class,
        UserflagPlaylistAction::REQUEST_KEY => UserflagPlaylistAction::class,
        UserflagPodcastEpisodeAction::REQUEST_KEY => UserflagPodcastEpisodeAction::class,
        UserflagSongAction::REQUEST_KEY => UserflagSongAction::class,
        UserflagVideoAction::REQUEST_KEY => UserflagVideoAction::class,
        RecentAlbumAction::REQUEST_KEY => RecentAlbumAction::class,
        RecentAlbumArtistAction::REQUEST_KEY => RecentAlbumArtistAction::class,
        RecentAlbumDiskAction::REQUEST_KEY => RecentAlbumDiskAction::class,
        RecentArtistAction::REQUEST_KEY => RecentArtistAction::class,
        RecentPlaylistAction::REQUEST_KEY => RecentPlaylistAction::class,
        RecentPodcastEpisodeAction::REQUEST_KEY => RecentPodcastEpisodeAction::class,
        RecentSongAction::REQUEST_KEY => RecentSongAction::class,
        RecentVideoAction::REQUEST_KEY => RecentVideoAction::class,
        WantedAction::REQUEST_KEY => WantedAction::class,
        ShareAction::REQUEST_KEY => ShareAction::class,
        UploadAction::REQUEST_KEY => UploadAction::class,
        GraphAction::REQUEST_KEY => GraphAction::class,
        ShowAction::REQUEST_KEY => ShowAction::class
    ],
    ShowAction::REQUEST_KEY
);
