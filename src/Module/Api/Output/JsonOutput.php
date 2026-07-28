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

use Ampache\Module\Api\Json4_Data;
use Ampache\Module\Api\Json5_Data;
use Ampache\Module\Api\Json6_Data;
use Ampache\Module\Api\Json8_Data;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use ArrayIterator;

final class JsonOutput implements ApiOutputInterface
{
    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 8 $apiVersion only api version 8 knows about album disks
     * @param array<int|string> $albumDisks
     * @param string[] $include
     */
    public function albumDisks(
        int $apiVersion,
        array $albumDisks,
        array $include,
        User $user,
        string $auth,
        bool $encode = true,
        bool $asObject = true,
    ): string {
        return Json8_Data::album_disks($albumDisks, $include, $user, $auth, $encode, $asObject);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 4|5|6|8 $apiVersion
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
            4 => Json4_Data::albums($albums, $include, $user, $auth),
            5 => Json5_Data::albums($albums, $include, $user, $auth),
            6 => Json6_Data::albums($albums, $include, $user, $auth, $encode, $asObject),
            8 => Json8_Data::albums($albums, $include, $user, $auth, $encode, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $artists
     * @param string[] $include
     */
    public function artists(int $apiVersion, array $artists, array $include, User $user, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::artists($artists, $include, $user, $auth, $asObject),
            6 => Json6_Data::artists($artists, $include, $user, $auth, $asObject),
            8 => Json8_Data::artists($artists, $include, $user, $auth, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param int[] $bookmarks Bookmark id's to include
     */
    public function bookmarks(int $apiVersion, array $bookmarks, string $auth, bool $include = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::bookmarks($bookmarks, $asObject),
            6 => Json6_Data::bookmarks($bookmarks, $auth, $include, $asObject),
            8 => Json8_Data::bookmarks($bookmarks, $auth, $include, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int, array{id: int|string, name: string}> $objects
     */
    public function browses(
        int $apiVersion,
        array $objects,
        string $parentType,
        string $childType,
        ?int $parentId = null,
        ?int $catalogId = null,
    ): string {
        return match ($apiVersion) {
            6 => Json6_Data::browses($objects, $parentType, $childType, $parentId, $catalogId),
            8 => Json8_Data::browses($objects, $parentType, $childType, $parentId, $catalogId),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $catalogs
     */
    public function catalogs(int $apiVersion, array $catalogs, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            // the version 5 builder only ever took integer ids
            5 => Json5_Data::catalogs(array_map(intval(...), $catalogs), $asObject),
            6 => Json6_Data::catalogs($catalogs, $asObject),
            8 => Json8_Data::catalogs($catalogs, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     */
    public function collectionItems(
        int $apiVersion,
        Collection $collection,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string {
        return Json8_Data::collection_items($collection, $user, $auth, $asObject);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param list<int> $objects
     */
    public function collections(
        int $apiVersion,
        array $objects,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string {
        return Json8_Data::collections($objects, $user, $auth, $asObject);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
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
            5 => Json5_Data::deleted($objectType, $objects),
            6 => Json6_Data::deleted($objectType, $objects),
            8 => Json8_Data::deleted($objectType, $objects),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int, array{
     *    object_type: LibraryItemEnum,
     *    object_id: int,
     *    track_id: int,
     *    track: int
     * }> $objectIds
     */
    public function democratic(
        int $apiVersion,
        array $objectIds,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string {
        return match ($apiVersion) {
            5 => Json5_Data::democratic($objectIds, $user, $auth, $asObject),
            6 => Json6_Data::democratic($objectIds, $user, $auth, $asObject),
            8 => Json8_Data::democratic($objectIds, $user, $auth, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 3|4|5|6|8 $apiVersion
     */
    public function error(int $apiVersion, int $code, string $message, string $action = '', string $type = ''): string
    {
        return match ($apiVersion) {
            3 => '',
            4 => Json4_Data::error((string) $code, $message),
            5 => Json5_Data::error($code, $message, $action, $type),
            6 => Json6_Data::error($code, $message, $action, $type),
            8 => Json8_Data::error($code, $message, $action, $type),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $objects
     */
    public function folders(
        int $apiVersion,
        array $objects,
        Folder $folder,
        User $user,
        string $auth,
    ): string {
        return Json8_Data::folders($objects, $folder, $user, $auth);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $genres
     */
    public function genres(int $apiVersion, array $genres, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::genres($genres, $asObject),
            6 => Json6_Data::genres($genres, $asObject),
            8 => Json8_Data::genres($genres, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     */
    public function index(int $apiVersion, array $objects, string $objectType, User $user, bool $include = false): string
    {
        return match ($apiVersion) {
            6 => Json6_Data::index($objects, $objectType, $user, $include),
            8 => Json8_Data::index($objects, $objectType, $user, $include),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * The json indexes have no full_xml flag, so it is dropped here.
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $objects
     */
    public function indexes(
        int $apiVersion,
        array $objects,
        string $objectType,
        User $user,
        string $auth,
        bool $fullXml = true,
        bool $include = false,
    ): string {
        return match ($apiVersion) {
            5 => Json5_Data::indexes($objects, $objectType, $user, $auth, $include),
            6 => Json6_Data::indexes($objects, $objectType, $user, $auth, $include),
            8 => Json8_Data::indexes($objects, $objectType, $user, $auth, $include),
        };
    }

    /**
     * The xml-only callback/object flags do not apply to json
     *
     * @param array<array-key, mixed> $array
     */
    public function keyedArray(
        int $apiVersion,
        array $array,
        bool $callback = false,
        bool|string $object = false,
    ): string {
        return (string) json_encode($array, JSON_PRETTY_PRINT);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $labels
     */
    public function labels(int $apiVersion, array $labels, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::labels($labels, $asObject),
            6 => Json6_Data::labels($labels, $asObject),
            8 => Json8_Data::labels($labels, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $licenses
     */
    public function licenses(int $apiVersion, array $licenses, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::licenses($licenses, $asObject),
            6 => Json6_Data::licenses($licenses, $asObject),
            8 => Json8_Data::licenses($licenses, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     */
    public function lists(int $apiVersion, array $objects): string
    {
        return match ($apiVersion) {
            6 => Json6_Data::lists($objects),
            8 => Json8_Data::lists($objects),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $liveStreams
     */
    public function liveStreams(int $apiVersion, array $liveStreams, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::live_streams($liveStreams, $asObject),
            6 => Json6_Data::live_streams($liveStreams, $asObject),
            8 => Json8_Data::live_streams($liveStreams, $asObject),
        };
    }

    /**
     * Render the result of a localplay command
     *
     * The json status reports `repeat`/`random` as booleans.
     *
     * @param array<string, mixed>|bool $result
     */
    public function localplayResult(
        int $apiVersion,
        string $command,
        array|bool $result,
    ): string {
        if (is_array($result)) {
            $result['repeat'] = (bool) ($result['repeat'] ?? false);
            $result['random'] = (bool) ($result['random'] ?? false);
        }

        return (string) json_encode(
            ['localplay' => ['command' => [$command => $result]]],
            JSON_PRETTY_PRINT
        );
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
            6 => Json6_Data::now_playing($results),
            8 => Json8_Data::now_playing($results),
        };
    }

    /**
     * Json encodes the payload verbatim; the xml item structure does not apply here
     *
     * @param array<mixed> $jsonPayload
     * @param array<int, array<string, mixed>> $xmlItems
     */
    public function objectArray(
        int $apiVersion,
        array $jsonPayload,
        array $xmlItems,
        string $item,
        string $objectType = '',
    ): string {
        return (string) json_encode($jsonPayload, JSON_PRETTY_PRINT);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $playlists
     */
    public function playlists(int $apiVersion, array $playlists, User $user, string $auth, bool $songs = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::playlists($playlists, $user, $auth, $songs, $asObject),
            6 => Json6_Data::playlists($playlists, $user, $auth, $songs, $asObject),
            8 => Json8_Data::playlists($playlists, $user, $auth, $songs, $asObject),
        };
    }

    /**
     * @param 4|5|6|8 $apiVersion
     * @param array<int|string> $result
     */
    public function podcastEpisodes(int $apiVersion, array $result, User $user, string $auth, bool $encode = true, bool $asObject = true): string
    {
        return match ($apiVersion) {
            4 => Json4_Data::podcast_episodes($result, $user, $auth, $asObject),
            5 => Json5_Data::podcast_episodes($result, $user, $auth, $asObject),
            6 => Json6_Data::podcast_episodes($result, $user, $auth, $encode, $asObject),
            8 => Json8_Data::podcast_episodes($result, $user, $auth, $encode, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $podcasts
     */
    public function podcasts(int $apiVersion, array $podcasts, User $user, string $auth, bool $episodes = false, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::podcasts($podcasts, $user, $auth, $episodes, $asObject),
            6 => Json6_Data::podcasts($podcasts, $user, $auth, $episodes, $asObject),
            8 => Json8_Data::podcasts($podcasts, $user, $auth, $episodes, $asObject),
        };
    }

    /**
     * Builds the keyed 'search' map that the json format uses for a grouped search
     *
     * @param 6|8 $apiVersion
     * @param array<string, array<int|string>> $results
     * @param array<string, int> $counts
     */
    public function searchGroup(
        int $apiVersion,
        array $results,
        array $counts,
        User $user,
        string $auth,
        int $offset,
        int $limit,
    ): string {
        $output = ['search' => []];

        $this->setOffset($apiVersion, $offset);
        $this->setLimit($apiVersion, $limit);

        foreach ($results as $key => $search) {
            if (array_key_exists($key, $counts)) {
                $this->setCount($apiVersion, $counts[$key]);
            }

            // the paged types are sliced here because their *_array output does not page itself
            if (
                in_array($key, ['album', 'song_artist', 'album_artist', 'artist', 'podcast_episode', 'song'], true)
                && $limit
                && (count($search) > $limit || $offset > 0)
            ) {
                $search = array_slice($search, $offset, $limit);
            }

            $output['search'][$key] = match ($key) {
                'album' => $this->albumsArray($apiVersion, $search, $user, $auth),
                'song_artist', 'album_artist', 'artist' => $this->artistsArray($apiVersion, $search, $user, $auth),
                'label' => $this->labelsArray($apiVersion, $search),
                'playlist' => $this->playlistsArray($apiVersion, $search, $user, $auth),
                'podcast' => $this->podcastsArray($apiVersion, $search, $user, $auth),
                'podcast_episode' => $this->podcastEpisodesArray($apiVersion, $search, $user, $auth),
                'genre', 'tag' => $this->genresArray($apiVersion, $search),
                'user' => $this->usersArray($apiVersion, $search),
                'video' => $this->videosArray($apiVersion, $search, $user, $auth),
                'song' => $this->songsArray($apiVersion, $search, $user, $auth),
                default => null,
            };

            if ($output['search'][$key] === null) {
                unset($output['search'][$key]);
            }
        }

        return (string) json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * Render a search result for a single object type
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $results
     */
    public function searchResult(
        int $apiVersion,
        string $type,
        array $results,
        User $user,
        string $auth,
        int $offset,
        int $limit,
        int $count,
    ): string {
        $this->setOffset($apiVersion, $offset);
        $this->setLimit($apiVersion, $limit);
        $this->setCount($apiVersion, $count);

        // only api version 8 has an album_disk formatter; older versions are turned away by
        // AdvancedSearchMethod before they reach this point
        if ($type === 'album_disk') {
            return ($apiVersion === 8)
                ? $this->albumDisks($apiVersion, $results, [], $user, $auth)
                : $this->writeEmpty($apiVersion, $type);
        }

        return match ($type) {
            'album' => $this->albums($apiVersion, $results, [], $user, $auth),
            'song_artist', 'album_artist', 'artist' => $this->artists($apiVersion, $results, [], $user, $auth),
            'label' => $this->labels($apiVersion, $results, $user),
            'playlist' => $this->playlists($apiVersion, $results, $user, $auth),
            'podcast' => $this->podcasts($apiVersion, $results, $user, $auth),
            'podcast_episode' => $this->podcastEpisodes($apiVersion, $results, $user, $auth),
            'genre', 'tag' => $this->genres($apiVersion, $results, $user),
            'user' => $this->users($apiVersion, $results),
            'video' => $this->videos($apiVersion, $results, $user, $auth),
            default => $this->songs($apiVersion, $results, $user, $auth),
        };
    }

    /**
     * @param 5|6|8 $apiVersion
     */
    public function setCount(int $apiVersion, int|string $count): void
    {
        match ($apiVersion) {
            5 => Json5_Data::set_count($count),
            6 => Json6_Data::set_count($count),
            8 => Json8_Data::set_count($count),
        };
    }

    /**
     * @param 4|5|6|8 $apiVersion
     */
    public function setLimit(int $apiVersion, int|string $limit): void
    {
        match ($apiVersion) {
            4 => Json4_Data::set_limit($limit),
            5 => Json5_Data::set_limit($limit),
            6 => Json6_Data::set_limit($limit),
            8 => Json8_Data::set_limit($limit),
        };
    }

    /**
     * @param 4|5|6|8 $apiVersion
     */
    public function setOffset(int $apiVersion, int|string $offset): void
    {
        match ($apiVersion) {
            4 => Json4_Data::set_offset($offset),
            5 => Json5_Data::set_offset($offset),
            6 => Json6_Data::set_offset($offset),
            8 => Json8_Data::set_offset($offset),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $shares
     */
    public function shares(int $apiVersion, array $shares, User $user, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::shares($shares, $user, $asObject),
            6 => Json6_Data::shares($shares, $user, $asObject),
            8 => Json8_Data::shares($shares, $user, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<Shoutbox> $shouts
     */
    public function shouts(
        int $apiVersion,
        array $shouts,
        bool $asObject = true,
    ): string {
        return match ($apiVersion) {
            5 => Json5_Data::shouts(new ArrayIterator($shouts), $asObject),
            6 => Json6_Data::shouts($shouts, $asObject),
            8 => Json8_Data::shouts($shouts, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $songs
     */
    public function songs(int $apiVersion, array $songs, User $user, string $auth, bool $encode = true, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::songs($songs, $user, $auth, $asObject),
            6 => Json6_Data::songs($songs, $user, $auth, $encode, $asObject),
            8 => Json8_Data::songs($songs, $user, $auth, $encode, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     */
    public function songTags(int $apiVersion, array $objects, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => Json6_Data::song_tags($objects, $auth, $asObject),
            8 => Json8_Data::song_tags($objects, $auth, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param list<array{'id': int, 'similarity': float}> $matches
     */
    public function sonicMatches(
        int $apiVersion,
        array $matches,
        User $user,
        string $auth,
        bool $asObject = true,
    ): string {
        return Json8_Data::sonic_matches($matches, $user, $auth, $asObject);
    }

    /**
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param 4|5|6|8 $apiVersion
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public function success(int $apiVersion, string $string, array $return_data = []): string
    {
        return match ($apiVersion) {
            4 => Json4_Data::success($string),
            5 => Json5_Data::success($string, $return_data),
            6 => Json6_Data::success($string, $return_data),
            8 => Json8_Data::success($string, $return_data),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param int[] $activities Activity id list
     */
    public function timeline(int $apiVersion, array $activities): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::timeline($activities),
            6 => Json6_Data::timeline($activities),
            8 => Json8_Data::timeline($activities),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     */
    public function user(int $apiVersion, User $user, bool $fullInfo, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::user($user, $fullInfo, $asObject),
            6 => Json6_Data::user($user, $fullInfo, $auth, $asObject),
            8 => Json8_Data::user($user, $fullInfo, $auth, $asObject),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $users
     */
    public function users(int $apiVersion, array $users): string
    {
        return match ($apiVersion) {
            // the version 5 builder only ever took integer ids
            5 => Json5_Data::users(array_map(intval(...), $users)),
            6 => Json6_Data::users($users),
            8 => Json8_Data::users($users),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 5|6|8 $apiVersion
     * @param array<int|string> $videos
     */
    public function videos(int $apiVersion, array $videos, User $user, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::videos($videos, $user, $auth, $asObject),
            6 => Json6_Data::videos($videos, $user, $auth, $asObject),
            8 => Json8_Data::videos($videos, $user, $auth, $asObject),
        };
    }

    /**
     * Generate an empty api result
     *
     * @param 5|6|8 $apiVersion
     */
    public function writeEmpty(int $apiVersion, ?string $emptyType): string
    {
        return match ($apiVersion) {
            5 => Json5_Data::empty((string) $emptyType),
            6 => Json6_Data::empty($emptyType),
            8 => Json8_Data::empty($emptyType),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function albumsArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::albums_array($objects, [], $user, $auth, false),
            8 => Json8_Data::albums_array($objects, [], $user, $auth, false),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function artistsArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::artists_array($objects, [], $user, $auth, false),
            8 => Json8_Data::artists_array($objects, [], $user, $auth, false),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function genresArray(int $apiVersion, array $objects): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::genres_array($objects),
            8 => Json8_Data::genres_array($objects),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function labelsArray(int $apiVersion, array $objects): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::labels_array($objects),
            8 => Json8_Data::labels_array($objects),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function playlistsArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::playlists_array($objects, $user, $auth),
            8 => Json8_Data::playlists_array($objects, $user, $auth),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function podcastEpisodesArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::podcast_episodes_array($objects, $user, $auth, false),
            8 => Json8_Data::podcast_episodes_array($objects, $user, $auth, false),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function podcastsArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::podcasts_array($objects, $user, $auth),
            8 => Json8_Data::podcasts_array($objects, $user, $auth),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function songsArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::songs_array($objects, $user, $auth),
            8 => Json8_Data::songs_array($objects, $user, $auth),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function usersArray(int $apiVersion, array $objects): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::users_array($objects),
            8 => Json8_Data::users_array($objects),
        };
    }

    /**
     * @param 6|8 $apiVersion
     * @param array<int|string> $objects
     *
     * @return array<mixed>
     */
    private function videosArray(int $apiVersion, array $objects, User $user, string $auth): array
    {
        return match ($apiVersion) {
            6 => Json6_Data::videos_array($objects, $user, $auth),
            8 => Json8_Data::videos_array($objects, $user, $auth),
        };
    }
}
