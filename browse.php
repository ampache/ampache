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
use Ampache\Module\Application\Browse\AlbumAction;
use Ampache\Module\Application\Browse\AlbumDiskAction;
use Ampache\Module\Application\Browse\AlbumArtistAction;
use Ampache\Module\Application\Browse\ArtistAction;
use Ampache\Module\Application\Browse\BroadcastAction;
use Ampache\Module\Application\Browse\CatalogAction;
use Ampache\Module\Application\Browse\ClipAction;
use Ampache\Module\Application\Browse\FileAction;
use Ampache\Module\Application\Browse\LabelAction;
use Ampache\Module\Application\Browse\LiveStreamAction;
use Ampache\Module\Application\Browse\MovieAction;
use Ampache\Module\Application\Browse\PersonalVideoAction;
use Ampache\Module\Application\Browse\PlaylistAction;
use Ampache\Module\Application\Browse\PodcastAction;
use Ampache\Module\Application\Browse\PodcastEpisodeAction;
use Ampache\Module\Application\Browse\PrivateMessageAction;
use Ampache\Module\Application\Browse\SmartPlaylistAction;
use Ampache\Module\Application\Browse\SongAction;
use Ampache\Module\Application\Browse\TagAction;
use Ampache\Module\Application\Browse\TvShowAction;
use Ampache\Module\Application\Browse\TvShowEpisodeAction;
use Ampache\Module\Application\Browse\TvShowSeasonAction;
use Ampache\Module\Application\Browse\VideoAction;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/src/Config/Init.php';

$dic->get(ApplicationRunner::class)->run(
    $dic->get(ServerRequestCreatorInterface::class)->fromGlobals(),
    [
        TagAction::REQUEST_KEY => TagAction::class,
        FileAction::REQUEST_KEY => FileAction::class,
        AlbumAction::REQUEST_KEY => AlbumAction::class,
        AlbumDiskAction::REQUEST_KEY => AlbumDiskAction::class,
        AlbumArtistAction::REQUEST_KEY => AlbumArtistAction::class,
        ArtistAction::REQUEST_KEY => ArtistAction::class,
        SongAction::REQUEST_KEY => SongAction::class,
        PlaylistAction::REQUEST_KEY => PlaylistAction::class,
        SmartPlaylistAction::REQUEST_KEY => SmartPlaylistAction::class,
        TvShowSeasonAction::REQUEST_KEY => TvShowSeasonAction::class,
        PodcastEpisodeAction::REQUEST_KEY => PodcastEpisodeAction::class,
        CatalogAction::REQUEST_KEY => CatalogAction::class,
        PrivateMessageAction::REQUEST_KEY => PrivateMessageAction::class,
        LiveStreamAction::REQUEST_KEY => LiveStreamAction::class,
        TvShowAction::REQUEST_KEY => TvShowAction::class,
        LabelAction::REQUEST_KEY => LabelAction::class,
        BroadcastAction::REQUEST_KEY => BroadcastAction::class,
        VideoAction::REQUEST_KEY => VideoAction::class,
        PodcastAction::REQUEST_KEY => PodcastAction::class,
        TvShowEpisodeAction::REQUEST_KEY => TvShowEpisodeAction::class,
        MovieAction::REQUEST_KEY => MovieAction::class,
        ClipAction::REQUEST_KEY => ClipAction::class,
        PersonalVideoAction::REQUEST_KEY => PersonalVideoAction::class,
    ],
    CatalogAction::REQUEST_KEY
);
