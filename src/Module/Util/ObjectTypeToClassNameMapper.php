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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\database_object;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\LibraryItemLoader;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Movie;
use Ampache\Repository\Model\ObjectTypeEnum;
use Ampache\Repository\Model\Personal_Video;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
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
 *
 * @see LibraryItemLoader
 */
final class ObjectTypeToClassNameMapper
{
    /** @var array<string, class-string<database_object>> */
    private const OBJECT_TYPE_MAPPING = [
        ObjectTypeEnum::ALBUM->value => Album::class,
        ObjectTypeEnum::ALBUM_DISK->value => AlbumDisk::class,
        ObjectTypeEnum::ART->value => Art::class,
        ObjectTypeEnum::ARTIST->value => Artist::class,
        ObjectTypeEnum::BOOKMARK->value => Bookmark::class,
        ObjectTypeEnum::CLIP->value => Clip::class,
        ObjectTypeEnum::GENRE->value => Tag::class,
        ObjectTypeEnum::LABEL->value => Label::class,
        ObjectTypeEnum::LIVE_STREAM->value => Live_Stream::class,
        ObjectTypeEnum::MOVIE->value => Movie::class,
        ObjectTypeEnum::PERSONAL_VIDEO->value => Personal_Video::class,
        ObjectTypeEnum::PLAYLIST->value => Playlist::class,
        ObjectTypeEnum::PODCAST->value => Podcast::class,
        ObjectTypeEnum::PODCAST_EPISODE->value => Podcast_Episode::class,
        ObjectTypeEnum::SEARCH->value => Search::class,
        ObjectTypeEnum::SHARE->value => Share::class,
        ObjectTypeEnum::SONG->value => Song::class,
        ObjectTypeEnum::SONG_PREVIEW->value => Song_Preview::class,
        ObjectTypeEnum::TAG_HIDDEN->value => Tag::class,
        ObjectTypeEnum::TAG->value => Tag::class,
        ObjectTypeEnum::TV_SHOW_EPISODE->value => TVShow_Episode::class,
        ObjectTypeEnum::TV_SHOW_SEASON->value => TVShow_Season::class,
        ObjectTypeEnum::USER->value => User::class,
        ObjectTypeEnum::VIDEO->value => Video::class,
        ObjectTypeEnum::WANTED->value => Wanted::class,
    ];

    /** @var array<class-string, ObjectTypeEnum> */
    public const VIDEO_TYPES = [
        Clip::class => ObjectTypeEnum::CLIP,
        Movie::class => ObjectTypeEnum::MOVIE,
        Personal_Video::class => ObjectTypeEnum::PERSONAL_VIDEO,
        TVShow_Episode::class => ObjectTypeEnum::TV_SHOW_EPISODE,
        TVShow_Season::class => ObjectTypeEnum::TV_SHOW_SEASON,
        Video::class => ObjectTypeEnum::VIDEO,
    ];

    /**
     * @return class-string<database_object>|string
     */
    public static function map(string $object_type): string
    {
        return self::OBJECT_TYPE_MAPPING[strtolower($object_type)] ?? $object_type;
    }

    /**
     * @param class-string<database_object> $className
     */
    public static function reverseMap(string $className): string
    {
        return array_flip(self::OBJECT_TYPE_MAPPING)[$className] ?? $className;
    }
}
