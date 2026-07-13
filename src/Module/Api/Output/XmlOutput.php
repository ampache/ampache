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

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;

final class XmlOutput implements ApiOutputInterface
{
    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 3|4|5|6|8 $apiVersion
     * @param array<int|string> $albums
     * @param string[] $include
     *
     */
    public function albums(
        int $apiVersion,
        array $albums,
        array $include,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string {
        return match ($apiVersion) {
            3 => Xml3_Data::albums($albums, $include, $user, $auth, $encode),
            4 => Xml4_Data::albums($albums, $include, $user, $auth, $encode),
            5 => Xml5_Data::albums($albums, $include, $user, $auth, $encode),
            6 => Xml6_Data::albums($albums, $include, $user, $auth, $encode),
            8 => Xml8_Data::albums($albums, $include, $user, $auth, $encode),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $artists
     * @param string[] $include
     */
    public function artists(int $apiVersion, array $artists, array $include, User $user, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::artists($artists, $include, $user, $auth),
            8 => Xml8_Data::artists($artists, $include, $user, $auth),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param int[] $bookmarks Bookmark id's to include
     */
    public function bookmarks(int $apiVersion, array $bookmarks, string $auth, bool $include = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::bookmarks($bookmarks, $auth, $include),
            8 => Xml8_Data::bookmarks($bookmarks, $auth, $include),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $catalogs
     */
    public function catalogs(int $apiVersion, array $catalogs, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::catalogs($catalogs, $user),
            8 => Xml8_Data::catalogs($catalogs, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int, array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int,
     *     update_time?: int,
     *     album?: int,
     *     artist?: int,
     *     podcast?: int,
     * }> $objects deleted object list
     */
    public function deleted(int $apiVersion, string $objectType, array $objects): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::deleted($objectType, $objects),
            8 => Xml8_Data::deleted($objectType, $objects),
        };
    }

    /**
     * At the moment, this method just acts a proxy
     *
     * @param 3|4|5|6|8 $apiVersion
     */
    public function error(int $apiVersion, int $code, string $message, string $action = '', string $type = ''): string
    {
        return match ($apiVersion) {
            3 => Xml3_Data::error($code, $message),
            4 => Xml4_Data::error((string) $code, $message),
            5 => Xml5_Data::error($code, $message, $action, $type),
            6 => Xml6_Data::error($code, $message, $action, $type),
            8 => Xml8_Data::error($code, $message, $action, $type),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $genres
     */
    public function genres(int $apiVersion, array $genres, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::genres($genres, $user),
            8 => Xml8_Data::genres($genres, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $labels
     */
    public function labels(int $apiVersion, array $labels, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::labels($labels, $user),
            8 => Xml8_Data::labels($labels, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $licenses
     */
    public function licenses(int $apiVersion, array $licenses, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::licenses($licenses, $user),
            8 => Xml8_Data::licenses($licenses, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $liveStreams
     */
    public function liveStreams(int $apiVersion, array $liveStreams, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::live_streams($liveStreams, $user),
            8 => Xml8_Data::live_streams($liveStreams, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $results
     */
    public function nowPlaying(int $apiVersion, array $results): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::now_playing($results),
            8 => Xml8_Data::now_playing($results),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $playlists
     */
    public function playlists(int $apiVersion, array $playlists, User $user, string $auth, bool $songs = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::playlists($playlists, $user, $auth, $songs),
            8 => Xml8_Data::playlists($playlists, $user, $auth, $songs),
        };
    }

    /**
     * @param 4|5|6|8 $apiVersion
     * @param array<int|string> $result
     */
    public function podcastEpisodes(int $apiVersion, array $result, User $user, string $auth, bool $encode = true, bool $asObject = true): string
    {
        return match ($apiVersion) {
            4 => Xml4_Data::podcast_episodes($result, $user, $auth),
            5 => Xml5_Data::podcast_episodes($result, $user, $auth),
            6 => Xml6_Data::podcast_episodes($result, $user, $auth),
            8 => Xml8_Data::podcast_episodes($result, $user, $auth),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $podcasts
     */
    public function podcasts(int $apiVersion, array $podcasts, User $user, string $auth, bool $episodes = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::podcasts($podcasts, $user, $auth, $episodes),
            8 => Xml8_Data::podcasts($podcasts, $user, $auth, $episodes),
        };
    }

    /**
     * @param 6|8 $apiVersion
     */
    public function setCount(int $apiVersion, int|string $count): void
    {
        match ($apiVersion) {
            6 => Xml6_Data::set_count($count),
            8 => Xml8_Data::set_count($count),
        };
    }

    /**
     * @param 3|4|5|6|8 $apiVersion
     */
    public function setLimit(int $apiVersion, int|string $limit): void
    {
        match ($apiVersion) {
            3 => Xml3_Data::set_limit($limit),
            4 => Xml4_Data::set_limit($limit),
            5 => Xml5_Data::set_limit($limit),
            6 => Xml6_Data::set_limit($limit),
            8 => Xml8_Data::set_limit($limit),
        };
    }

    /**
     * @param 3|4|5|6|8 $apiVersion
     */
    public function setOffset(int $apiVersion, int|string $offset): void
    {
        match ($apiVersion) {
            3 => Xml3_Data::set_offset($offset),
            4 => Xml4_Data::set_offset($offset),
            5 => Xml5_Data::set_offset($offset),
            6 => Xml6_Data::set_offset($offset),
            8 => Xml8_Data::set_offset($offset),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $shares
     */
    public function shares(int $apiVersion, array $shares, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::shares($shares, $user),
            8 => Xml8_Data::shares($shares, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $songs
     */
    public function songs(int $apiVersion, array $songs, User $user, string $auth, bool $encode = true, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::songs($songs, $user, $auth),
            8 => Xml8_Data::songs($songs, $user, $auth),
        };
    }

    /**
     * This generates a standard XML Success message
     * nothing fancy here...
     *
     * @param 3|4|5|6|8 $apiVersion
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public function success(int $apiVersion, string $string, array $return_data = []): string
    {
        return match ($apiVersion) {
            3 => Xml3_Data::success(),
            4 => Xml4_Data::success($string),
            5 => Xml5_Data::success($string, $return_data),
            6 => Xml6_Data::success($string, $return_data),
            8 => Xml8_Data::success($string, $return_data),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     */
    public function user(int $apiVersion, User $user, bool $fullInfo, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::user($user, $fullInfo, $auth),
            8 => Xml8_Data::user($user, $fullInfo, $auth),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $users
     */
    public function users(int $apiVersion, array $users): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::users($users),
            8 => Xml8_Data::users($users),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $videos
     */
    public function videos(int $apiVersion, array $videos, User $user, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::videos($videos, $user, $auth),
            8 => Xml8_Data::videos($videos, $user, $auth),
        };
    }

    /**
     * Generate an empty api result
     *
     * @param 6|8 $apiVersion
     */
    public function writeEmpty(int $apiVersion, ?string $emptyType): string
    {
        return match ($apiVersion) {
            6 => Xml6_Data::empty(),
            8 => Xml8_Data::empty(),
        };
    }
}
