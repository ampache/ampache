<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=1);

namespace Ampache\Module\Util;

use Ampache\Model\Album;
use Ampache\Model\Artist;
use Ampache\Model\Clip;
use Ampache\Model\Label;
use Ampache\Model\Live_Stream;
use Ampache\Model\Movie;
use Ampache\Model\Personal_Video;
use Ampache\Model\Playlist;
use Ampache\Model\Podcast;
use Ampache\Model\Podcast_Episode;
use Ampache\Model\Search;
use Ampache\Model\Share;
use Ampache\Model\Song;
use Ampache\Model\Art;
use Ampache\Model\TVShow_Episode;
use Ampache\Model\User;
use Ampache\Model\Video;

/**
 * This class maps object types like `album` to their corresponding php class name (if known)
 *
 * @deprecated Remove after every usage has been removed
 */
final class ObjectTypeToClassNameMapper
{
    private const OBJECT_TYPE_MAPPING = [
        'album' => Album::class,
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
