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

namespace Ampache\Repository;

use Ampache\Repository\Model\UseractivityInterface;

interface UserActivityRepositoryInterface
{
    /**
     * @return UseractivityInterface[]
     */
    public function getFriendsActivities(
        int $userId,
        int $limit = 0,
        int $since = 0
    ): array;

    /**
     * @return UseractivityInterface[]
     */
    public function getActivities(
        int $userId,
        int $limit = 0,
        int $since = 0
    ): array;

    /**
     * Delete activity by date
     */
    public function deleteByDate(
        int $date,
        string $action,
        int $userId = 0
    ): void;

    /**
     * Remove activities for items that no longer exist.
     */
    public function collectGarbage(
        ?string $objectType = null,
        ?int $objectId = null
    ): void;

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerSongEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $songName,
        ?string $artistName,
        ?string $albumName,
        ?string $songMbId,
        ?string $artistMbId,
        ?string $albumMbId
    ): void;

    /**
     * Inserts the necessary data to register a generic action on an object
     *
     * @todo Replace when active record models are available
     */
    public function registerGenericEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date
    ): void;

    /**
     * Inserts the necessary data to register an artist related action
     *
     * @todo Replace when active record models are available
     */
    public function registerArtistEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $artistName,
        ?string $artistMbId
    ): void;

    /**
     * Inserts the necessary data to register the playback of a song
     *
     * @todo Replace when active record models are available
     */
    public function registerAlbumEntry(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        int $date,
        ?string $artistName,
        ?string $albumName,
        ?string $artistMbId,
        ?string $albumMbId
    ): void;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;
}
