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

use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Api\Xml5_Data;
use Ampache\Module\Api\Xml6_Data;
use Ampache\Module\Api\Xml8_Data;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use ArrayIterator;

final class XmlOutput implements ApiOutputInterface
{
    public function __construct(
        private Xml5_Data $xml5Data,
        private Xml6_Data $xml6Data,
        private Xml8_Data $xml8Data,
    ) {}

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
        return $this->xml8Data->album_disks($albumDisks, $include, $user, $auth, $encode);
    }

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
            5 => $this->xml5Data->albums($albums, $include, $user, $auth, $encode),
            6 => $this->xml6Data->albums($albums, $include, $user, $auth, $encode),
            8 => $this->xml8Data->albums($albums, $include, $user, $auth, $encode),
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
            5 => $this->xml5Data->artists($artists, $include, $user, $auth),
            6 => $this->xml6Data->artists($artists, $include, $user, $auth),
            8 => $this->xml8Data->artists($artists, $include, $user, $auth),
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
            5 => $this->xml5Data->bookmarks($bookmarks),
            6 => $this->xml6Data->bookmarks($bookmarks, $auth, $include),
            8 => $this->xml8Data->bookmarks($bookmarks, $auth, $include),
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
            6 => $this->xml6Data->browses($objects, $parentType, $childType, $parentId, $catalogId),
            8 => $this->xml8Data->browses($objects, $parentType, $childType, $parentId, $catalogId),
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
            5 => $this->xml5Data->catalogs(array_map(intval(...), $catalogs), $user),
            6 => $this->xml6Data->catalogs($catalogs, $user),
            8 => $this->xml8Data->catalogs($catalogs, $user),
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
        unset($asObject);

        return $this->xml8Data->collection_items($collection, $user, $auth);
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
        unset($asObject);

        return $this->xml8Data->collections($objects, $user, $auth);
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
            5 => $this->xml5Data->deleted($objectType, $objects),
            6 => $this->xml6Data->deleted($objectType, $objects),
            8 => $this->xml8Data->deleted($objectType, $objects),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * The json-only object flag does not apply to xml.
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
            5 => $this->xml5Data->democratic($objectIds, $user, $auth),
            6 => $this->xml6Data->democratic($objectIds, $user, $auth),
            8 => $this->xml8Data->democratic($objectIds, $user, $auth),
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
        return $this->xml8Data->folders($objects, $folder, $user, $auth);
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
            5 => $this->xml5Data->genres($genres, $user),
            6 => $this->xml6Data->genres($genres, $user),
            8 => $this->xml8Data->genres($genres, $user),
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
            6 => $this->xml6Data->index($objects, $objectType, $user, $include),
            8 => $this->xml8Data->index($objects, $objectType, $user, $include),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
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
            5 => $this->xml5Data->indexes($objects, $objectType, $user, $auth, $fullXml, $include),
            6 => $this->xml6Data->indexes($objects, $objectType, $user, $auth, $fullXml, $include),
            8 => $this->xml8Data->indexes($objects, $objectType, $user, $auth, $fullXml, $include),
        };
    }

    /**
     * The keyed-array builder is version agnostic and shared by both api versions
     *
     * @param 6|8 $apiVersion
     * @param array<array-key, mixed> $array
     */
    public function keyedArray(
        int $apiVersion,
        array $array,
        bool $callback = false,
        bool|string $object = false,
    ): string {
        return Api::keyed_array($array, $callback, $object);
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
            5 => $this->xml5Data->labels($labels, $user),
            6 => $this->xml6Data->labels($labels, $user),
            8 => $this->xml8Data->labels($labels, $user),
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
            5 => $this->xml5Data->licenses($licenses, $user),
            6 => $this->xml6Data->licenses($licenses, $user),
            8 => $this->xml8Data->licenses($licenses, $user),
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
            6 => $this->xml6Data->lists($objects),
            8 => $this->xml8Data->lists($objects),
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
            5 => $this->xml5Data->live_streams($liveStreams, $user),
            6 => $this->xml6Data->live_streams($liveStreams, $user),
            8 => $this->xml8Data->live_streams($liveStreams, $user),
        };
    }

    /**
     * Render the result of a localplay command
     *
     * Unlike json, the xml status leaves `repeat`/`random` exactly as the controller returned them.
     *
     * @param array<string, mixed>|bool $result
     */
    public function localplayResult(
        int $apiVersion,
        string $command,
        array|bool $result,
    ): string {
        return Api::keyed_array(['localplay' => ['command' => [$command => $result]]]);
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
            6 => $this->xml6Data->now_playing($results),
            8 => $this->xml8Data->now_playing($results),
        };
    }

    /**
     * Builds the item document from $xmlItems; the json payload does not apply here
     *
     * The object-array builder is version agnostic and shared by both api versions.
     *
     * @param 6|8 $apiVersion
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
        return Api::object_array($xmlItems, $item, $objectType);
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
            5 => $this->xml5Data->playlists($playlists, $user, $auth),
            6 => $this->xml6Data->playlists($playlists, $user, $auth, $songs),
            8 => $this->xml8Data->playlists($playlists, $user, $auth, $songs),
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
            5 => $this->xml5Data->podcast_episodes($result, $user, $auth),
            6 => $this->xml6Data->podcast_episodes($result, $user, $auth),
            8 => $this->xml8Data->podcast_episodes($result, $user, $auth),
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
            5 => $this->xml5Data->podcasts($podcasts, $user, $auth, $episodes),
            6 => $this->xml6Data->podcasts($podcasts, $user, $auth, $episodes),
            8 => $this->xml8Data->podcasts($podcasts, $user, $auth, $episodes),
        };
    }

    /**
     * The xml format renders a grouped search as a single searches document
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
        $this->setOffset($apiVersion, $offset);
        $this->setLimit($apiVersion, $limit);

        // don't set count here as each type of object will count themselves
        return match ($apiVersion) {
            6 => $this->xml6Data->searches($results, $counts, $user, $auth),
            8 => $this->xml8Data->searches($results, $counts, $user, $auth),
        };
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
            5 => $this->xml5Data->set_count($count),
            6 => $this->xml6Data->set_count($count),
            8 => $this->xml8Data->set_count($count),
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
            5 => $this->xml5Data->set_limit($limit),
            6 => $this->xml6Data->set_limit($limit),
            8 => $this->xml8Data->set_limit($limit),
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
            5 => $this->xml5Data->set_offset($offset),
            6 => $this->xml6Data->set_offset($offset),
            8 => $this->xml8Data->set_offset($offset),
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
            5 => $this->xml5Data->shares($shares, $user),
            6 => $this->xml6Data->shares($shares, $user),
            8 => $this->xml8Data->shares($shares, $user),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * The json-only object flag does not apply to xml.
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
            5 => $this->xml5Data->shouts(new ArrayIterator($shouts)),
            6 => $this->xml6Data->shouts($shouts),
            8 => $this->xml8Data->shouts($shouts),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 3|4|5|6|8 $apiVersion
     * @param array<int|string> $songs
     */
    public function songs(int $apiVersion, array $songs, User $user, string $auth, bool $encode = true, bool $asObject = true): string
    {
        return match ($apiVersion) {
            3 => Xml3_Data::songs($songs, $user, $auth),
            4 => Xml4_Data::songs($songs, $user, $auth),
            5 => $this->xml5Data->songs($songs, $user, $auth),
            6 => $this->xml6Data->songs($songs, $user, $auth),
            8 => $this->xml8Data->songs($songs, $user, $auth),
        };
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param 6|8 $apiVersion
     */
    public function songTags(int $apiVersion, array $objects, string $auth, bool $asObject = true): string
    {
        return match ($apiVersion) {
            6 => $this->xml6Data->song_tags($objects, $auth),
            8 => $this->xml8Data->song_tags($objects, $auth),
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
        unset($asObject);

        return $this->xml8Data->sonic_matches($matches, $user, $auth);
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
     * @param 5|6|8 $apiVersion
     * @param int[] $activities Activity id list
     */
    public function timeline(int $apiVersion, array $activities): string
    {
        return match ($apiVersion) {
            5 => $this->xml5Data->timeline($activities),
            6 => $this->xml6Data->timeline($activities),
            8 => $this->xml8Data->timeline($activities),
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
            5 => $this->xml5Data->user($user, $fullInfo),
            6 => $this->xml6Data->user($user, $fullInfo, $auth),
            8 => $this->xml8Data->user($user, $fullInfo, $auth),
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
            5 => $this->xml5Data->users($users),
            6 => $this->xml6Data->users($users),
            8 => $this->xml8Data->users($users),
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
            5 => $this->xml5Data->videos($videos, $user, $auth),
            6 => $this->xml6Data->videos($videos, $user, $auth),
            8 => $this->xml8Data->videos($videos, $user, $auth),
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
            5 => Xml5_Data::empty(),
            6 => Xml6_Data::empty(),
            8 => Xml8_Data::empty(),
        };
    }
}
