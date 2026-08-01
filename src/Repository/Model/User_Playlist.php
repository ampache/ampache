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

namespace Ampache\Repository\Model;

use Ampache\Repository\UserPlaylistRepositoryInterface;

/**
 * UserPlaylist Class
 *
 * This class handles the user playlists in Ampache. It handles the
 * user_playlist table creating a global play queue for each user
 */
class User_Playlist extends database_object
{
    protected const string DB_TABLENAME = 'user_playlist';

    public string $client;
    public int $user;

    /**
     * Constructor
     * This takes a user_id as an optional argument and gathers the
     * information.  If no user_id is passed or the requested one isn't
     * found, return false.
     */
    public function __construct(
        ?int $user_id = 0,
        ?string $client = null,
    ) {
        if (!$user_id) {
            return;
        }

        $client ??= $this->get_latest();
        if (empty($client)) {
            return;
        }

        $this->user   = $user_id;
        $this->client = substr($client, 0, 254);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserPlaylistRepository(): UserPlaylistRepositoryInterface
    {
        global $dic;

        return $dic->get(UserPlaylistRepositoryInterface::class);
    }

    /**
     * add_items
     * Add an array of songs to the playlist
     */
    public function add_items(array $data, int $time): void
    {
        self::getUserPlaylistRepository()->addItems($this->user, $this->client, $time, array_values($data));
    }

    /**
     * clear
     * This clears all the objects out of a user's playlist for that client
     */
    public function clear(): void
    {
        self::getUserPlaylistRepository()->clear($this->user, $this->client);
    }

    /**
     * get_count
     * This returns a count of the total number of tracks that are in this playlist
     */
    public function get_count(): int
    {
        $results = ['count' => self::getUserPlaylistRepository()->getCount($this->user, $this->client)];

        return (int) $results['count'];
    }

    /**
     * get_current_object
     * This returns the next object in the user_playlist.
     * @return array{}|array{
     *     object_type: string,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     current_track: int,
     *     current_time: int
     * }
     */
    public function get_current_object(): array
    {
        $items   = [];
        $results = self::getUserPlaylistRepository()->getCurrentRow($this->user);
        if ($results !== []) {
            $items = [
                'object_type' => $results['object_type'],
                'object_id' => (int) $results['object_id'],
                'track_id' => (int) $results['object_id'],
                'track' => (int) $results['track'],
                'current_track' => (int) $results['current_track'],
                'current_time' => (int) $results['current_time'],
            ];
        }

        return $items;
    }

    /**
     * get_items
     * Returns an array of all object_ids currently in this User_Playlist.
     * @return array<int, array{
     *     object_type: string,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     current_track: int,
     *     current_time: int
     * }>
     */
    public function get_items(): array
    {
        $items = [];
        foreach (self::getUserPlaylistRepository()->getItems($this->user, $this->client) as $results) {
            $items[] = [
                'object_type' => $results['object_type'],
                'object_id' => $results['object_id'],
                'track_id' => $results['object_id'],
                'track' => $results['track'],
                'current_track' => $results['current_track'],
                'current_time' => $results['current_time'],
            ];
        }

        return $items;
    }

    /**
     * get_latest
     * get the most recent playqueue for the user
     */
    public function get_latest(): string
    {
        return self::getUserPlaylistRepository()->getLatestClient($this->user);
    }

    /**
     * get_time
     * This returns a count of the total number of tracks that are in this playlist
     */
    public function get_time(): int
    {
        return self::getUserPlaylistRepository()->getTime($this->user, $this->client) ?? time();
    }

    /**
     * set_current_id
     * set the active object using the row id in user_playlist.
     */
    public function set_current_id(string $object_type, int $track, int $position): void
    {
        self::getUserPlaylistRepository()->setCurrentByTrack($this->user, $object_type, $track, $position);
    }

    /**
     * set_current_object
     * set the active object in the user_playlist.
     */
    public function set_current_object(string $object_type, int $object_id, int $position): void
    {
        self::getUserPlaylistRepository()->setCurrentByObject($this->user, $object_type, $object_id, $position);
    }
}
