<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Util;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\License;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Movie;
use Ampache\Repository\Model\Personal_Video;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\TVShow_Episode;
use Ampache\Repository\Model\TVShow_Season;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\Model\Wanted;

/**
 * This class maps object types like `album` to their corresponding php class name (if known)
 *
 * @deprecated Remove after every usage has been removed
 */
final class ObjectTypeToClassNameMapper
{
    private const OBJECT_TYPE_MAPPING = [
        'album' => Album::class,
        'album_disk' => AlbumDisk::class,
        'art' => Art::class,
        'artist' => Artist::class,
        'bookmark' => Bookmark::class,
        'clip' => Clip::class,
        'genre' => Tag::class,
        'label' => Label::class,
        'license' => License::class,
        'live_stream' => Live_Stream::class,
        'movie' => Movie::class,
        'personal_video' => Personal_Video::class,
        'playlist' => Playlist::class,
        'podcast' => Podcast::class,
        'podcast_episode' => Podcast_Episode::class,
        'search' => Search::class,
        'share' => Share::class,
        'song' => Song::class,
        'song_preview' => Song_Preview::class,
        'tag_hidden' => Tag::class,
        'tag' => Tag::class,
        'tvshow_episode' => TVShow_Episode::class,
        'tvshow_season' => TVShow_Season::class,
        'user' => User::class,
        'video' => Video::class,
        'wanted' => Wanted::class,
    ];

    public const VIDEO_TYPES = [
        Clip::class => 'clip',
        Movie::class => 'movie',
        Personal_Video::class => 'personal_video',
        TVShow_Episode::class => 'tvshow_episode',
        TVShow_Season::class => 'tvshow_season',
        Video::class => 'video',
    ];

    public static function map(string $object_type): string
    {
        return self::OBJECT_TYPE_MAPPING[strtolower($object_type)] ?? $object_type;
    }

    public static function reverseMap(string $className): string
    {
        return array_flip(self::OBJECT_TYPE_MAPPING)[$className] ?? $className;
    }
}
