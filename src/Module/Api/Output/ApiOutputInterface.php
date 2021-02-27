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

namespace Ampache\Module\Api\Output;

use Ampache\Repository\Model\User;

interface ApiOutputInterface
{
    /**
     * This generates an error message
     */
    public function error(
        int $code,
        string $message,
        string $action,
        string $type
    ): string;

    /**
     * @param int[] $albums
     * @param array $include
     * @param int|null $user_id
     * @param bool $encode
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        int $limit = 0,
        int $offset = 0
    );

    /**
     * Returns an empty response
     *
     * @param string $type object type
     *
     * @return string return empty JSON message
     */
    public function emptyResult(string $type): string;

    /**
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param int[] $artists
     * @param array $include
     * @param null|int $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function artists(
        array $artists,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        int $limit = 0,
        int $offset = 0
    );

    /**
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     *
     * @param int[] $songs
     * @param int|null $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param bool $full_xml
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function songs(
        array $songs,
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        bool $full_xml = true,
        int $limit = 0,
        int $offset = 0
    );

    /**
     * This handles creating a list of users
     *
     * @param int[] $users User identifier list
     *
     * @return string
     */
    public function users(
        array $users
    ): string;

    /**
     * This handles creating a result for a shout list
     *
     * @param int[] $shouts List of shout ids
     *
     * @return string
     */
    public function shouts(array $shoutIds): string;

    /**
     * This handles creating a result for a user
     */
    public function user(User $user, bool $fullinfo): string;

    /**
     * This returns genres to the user
     *
     * @param int[] $tagIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function genres(
        array $tagIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This builds the xml document for displaying video objects
     *
     * @param int[] $videoIds
     * @param int|null $userId
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function videos(
        array $videoIds,
        ?int $userId = null,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    public function success(string $string, array $return_data = []): string;

    /**
     * This returns licenses to the user
     *
     * @param int[] $licenseIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function licenses(
        array $licenseIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This returns labels to the user
     *
     * @param int[] $labelIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function labels(
        array $labelIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This returns podcasts to the user
     *
     * @param int[] $podcasts
     * @param int $userId
     * @param boolean $episodes include the episodes of the podcast
     * @param int $limit
     * @param int $offset
     */
    public function podcasts(
        array $podcasts,
        int $userId,
        bool $episodes = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This returns podcasts to the user
     *
     * @param int[] $podcastEpisodeIds
     * @param int $userId
     * @param bool $simple just return the data as an array for pretty somewhere else
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function podcast_episodes(
        array $podcastEpisodeIds,
        int $userId,
        bool $simple = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    );

    /**
     * This returns the playlists to the user
     *
     * @param int[] $playlistIds
     * @param int $userId
     * @param bool $songs
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function playlists(
        array $playlists,
        int $userId,
        bool $songs = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This returns a dict to the user
     */
    public function dict(
        array $data,
        bool $xmlOutput = true,
        ?string $tagName = null
    ): string;

    /**
     * This returns catalogs to the user
     *
     * @param int[] $catalogIds group of catalog id's
     * @param bool $asObject (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     */
    public function catalogs(
        array $catalogIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This return user activity to the user
     *
     * @param int[] $activityIds Activity identifier list
     */
    public function timeline(
        array $activityIds
    ): string;

    /**
     * This returns bookmarks to the user
     *
     * @param int[] $bookmarkIds
     * @param int $limit
     * @param int $offset
     */
    public function bookmarks(
        array $bookmarkIds,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * This returns shares to the user
     *
     * @param int[] $shareIds Share id's to include
     * @param bool  $asAsOject
     * @param int   $limit
     * @param int   $offset
     */
    public function shares(
        array $shareIds,
        bool $asAsOject = true,
        int $limit = 0,
        int $offset = 0
    ): string;

    /**
     * Formats a list of arrays
     *
     * @param array  $array
     * @param string $item
     */
    public function object_array(array $array, string $item): string;

    /**
     * This takes an array of object_ids and returns a result based on type
     *
     * @param int[]    $objects Array of object_ids
     * @param string   $type
     * @param null|int $user_id
     * @param bool     $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @param int      $limit
     * @param int      $offset
     */
    public function indexes(
        array $objectIds,
        string $type,
        ?int $userId = null,
        bool $include = false,
        bool $full_xml = false,
        int $limit = 0,
        int $offset = 0
    ): string;
}
