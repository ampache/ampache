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

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\User;

interface ApiOutputInterface
{
    /**
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
    ): string;

    /**
     * @param array<int|string> $artists
     * @param string[] $include
     */
    public function artists(
        int $apiVersion,
        array $artists,
        array $include,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string;

    /**
     * @param int[] $bookmarks Bookmark id's to include
     */
    public function bookmarks(
        int $apiVersion,
        array $bookmarks,
        string $auth,
        bool $include = false,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $catalogs
     */
    public function catalogs(
        int $apiVersion,
        array $catalogs,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * Generate a list of deleted objects
     *
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
    public function deleted(
        int $apiVersion,
        string $objectType,
        array $objects,
    ): string;

    /**
     * This generates an error message
     */
    public function error(
        int $apiVersion,
        int $code,
        string $message,
        string $action = '',
        string $type = '',
    ): string;

    /**
     * @param array<int|string> $genres
     */
    public function genres(
        int $apiVersion,
        array $genres,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * Generate an index of object ids for a single object type
     *
     * @param array<int|string> $objects
     */
    public function index(
        int $apiVersion,
        array $objects,
        string $objectType,
        User $user,
        bool $include = false,
    ): string;

    /**
     * @param array<int|string> $labels
     */
    public function labels(
        int $apiVersion,
        array $labels,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $licenses
     */
    public function licenses(
        int $apiVersion,
        array $licenses,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * Generate a name/id list result
     *
     * @param array<int, array{id: int|string, name: string}> $objects
     */
    public function lists(
        int $apiVersion,
        array $objects,
    ): string;

    /**
     * @param array<int|string> $liveStreams
     */
    public function liveStreams(
        int $apiVersion,
        array $liveStreams,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $results
     */
    public function nowPlaying(
        int $apiVersion,
        array $results,
    ): string;

    /**
     * @param array<int|string> $playlists
     */
    public function playlists(
        int $apiVersion,
        array $playlists,
        User $user,
        string $auth,
        bool $songs = false,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $result
     */
    public function podcastEpisodes(
        int $apiVersion,
        array $result,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $podcasts
     */
    public function podcasts(
        int $apiVersion,
        array $podcasts,
        User $user,
        string $auth,
        bool $episodes = false,
        bool $asObject = true,
    ): string;

    public function setCount(int $apiVersion, int|string $count): void;

    public function setLimit(int $apiVersion, int|string $limit): void;

    public function setOffset(int $apiVersion, int|string $offset): void;

    /**
     * @param array<int|string> $shares
     */
    public function shares(
        int $apiVersion,
        array $shares,
        User $user,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $songs
     */
    public function songs(
        int $apiVersion,
        array $songs,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $objects
     */
    public function songTags(
        int $apiVersion,
        array $objects,
        string $auth,
        bool $asObject = true,
    ): string;

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public function success(int $apiVersion, string $string, array $return_data = []): string;

    /**
     * Generate a user activity timeline
     *
     * @param int[] $activities Activity id list
     */
    public function timeline(
        int $apiVersion,
        array $activities,
    ): string;

    /**
     * Generate a single user result
     */
    public function user(
        int $apiVersion,
        User $user,
        bool $fullInfo,
        string $auth,
        bool $asObject = true,
    ): string;

    /**
     * @param array<int|string> $users
     */
    public function users(
        int $apiVersion,
        array $users,
    ): string;

    /**
     * @param array<int|string> $videos
     */
    public function videos(
        int $apiVersion,
        array $videos,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string;

    /**
     * Generate an empty api result
     */
    public function writeEmpty(
        int $apiVersion,
        ?string $emptyType,
    ): string;
}
