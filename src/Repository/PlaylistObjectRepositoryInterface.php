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

namespace Ampache\Repository;

use Ampache\Repository\Model\playlist_object;

/**
 * The writes `playlist` and `search` share.
 */
interface PlaylistObjectRepositoryInterface
{
    /**
     * Removes collaborator rows whose list no longer exists
     */
    public function collectGarbage(): void;

    /**
     * Drops every collaborator of the list, for use when the list itself is deleted
     */
    public function deleteCollaborators(playlist_object $item): void;

    /**
     * Stores the cached item count
     */
    public function setLastCount(playlist_object $item, int $count): void;

    /**
     * Stores the cached total duration
     */
    public function setLastDuration(playlist_object $item, int $duration): void;

    /**
     * Replaces the set of users allowed to collaborate on the list
     *
     * @param int[] $userIds
     */
    public function updateCollaborators(playlist_object $item, array $userIds): void;
}
