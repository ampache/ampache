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

use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;

/**
 * Manages search related database access
 *
 * Tables: `search`
 */
interface SearchRepositoryInterface extends PlaylistObjectRepositoryInterface
{
    /**
     * Removes the saved search
     */
    public function delete(Search $search): void;

    /**
     * Stores a new saved search and returns its id, or null when nothing was written
     */
    public function insert(Search $search, User $user, int $time): ?int;

    /**
     * Whether the user already has a saved search of this name and type
     */
    public function nameExists(string $name, int $userId, ?string $type): bool;
}
