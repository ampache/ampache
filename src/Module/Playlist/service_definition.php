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

namespace Ampache\Module\Playlist;

use Ampache\Module\Playlist\Search\AlbumSearch;
use Ampache\Module\Playlist\Search\ArtistSearch;
use Ampache\Module\Playlist\Search\LabelSearch;
use Ampache\Module\Playlist\Search\PlaylistSearch;
use Ampache\Module\Playlist\Search\PodcastEpisodeSearch;
use Ampache\Module\Playlist\Search\PodcastSearch;
use Ampache\Module\Playlist\Search\SongSearch;
use Ampache\Module\Playlist\Search\TagSearch;
use Ampache\Module\Playlist\Search\UserSearch;
use Ampache\Module\Playlist\Search\VideoSearch;

use function DI\autowire;

return [
    PlaylistExporterInterface::class => autowire(PlaylistExporter::class),
    PlaylistLoaderInterface::class => autowire(PlaylistLoader::class),
    AlbumSearch::class => autowire(),
    ArtistSearch::class => autowire(),
    LabelSearch::class => autowire(),
    PlaylistSearch::class => autowire(),
    PodcastEpisodeSearch::class => autowire(),
    PodcastSearch::class => autowire(),
    SongSearch::class => autowire(),
    TagSearch::class => autowire(),
    UserSearch::class => autowire(),
    VideoSearch::class => autowire(),
];
