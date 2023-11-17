<?php

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

namespace Ampache\Repository;

interface UserActivityRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getFriendsActivities(
        int $user_id,
        int $limit = 0,
        int $since = 0
    ): array;

    /**
     * @return int[]
     */
    public function getActivities(
        int $user_id,
        int $limit = 0,
        int $since = 0
    ): array;

    /**
     * Delete activity by date
     */
    public function deleteByDate(
        int $date,
        string $action,
        int $user_id = 0
    ): void;

    /**
     * Remove activities for items that no longer exist.
     */
    public function collectGarbage(
        ?string $object_type = null,
        ?int $object_id = null
    ): void;

    /**
     * Inserts the necessary data to register a generic action on an object
     *
     * @todo Replace when active record models are available
     */
    public function registerGenericEntry(
        int $userId,
        string $action,
        string $object_type,
        int $objectId,
        int $date
    ): void;
}
