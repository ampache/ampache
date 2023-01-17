<?php
/*
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

namespace Ampache\Module\Util;

use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\Label;
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
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\TVShow_Episode;
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
        'song' => Song::class,
        'playlist' => Playlist::class,
        'artist' => Artist::class,
        'art' => Art::class,
        'search' => Search::class,
        'video' => Video::class,
        'share' => Share::class,
        'movie' => Movie::class,
        'personal_video' => Personal_Video::class,
        'user' => User::class,
        'live_stream' => Live_Stream::class,
        'podcast_episode' => Podcast_Episode::class,
        'tvshow_episode' => TVShow_Episode::class,
        'clip' => Clip::class,
        'label' => Label::class,
        'podcast' => Podcast::class,
        'genre' => Tag::class,
        'tag' => Tag::class,
        'tag_hidden' => Tag::class,
        'wanted' => Wanted::class,
    ];

    public const VIDEO_TYPES = [
        TVShow_Episode::class => 'tvshow_episode',
        Movie::class => 'movie',
        Clip::class => 'clip',
        Personal_Video::class => 'personal_video',
        Video::class => 'video',
    ];

    public static function map(string $object_type)
    {
        return self::OBJECT_TYPE_MAPPING[strtolower($object_type)] ?? $object_type;
    }

    public static function reverseMap(string $class_name): string
    {
        return array_flip(self::OBJECT_TYPE_MAPPING)[$class_name] ?? $class_name;
    }
}
