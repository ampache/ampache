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

/**
 * Provides access to the `tmp_browse` table, where a browse keeps its state and its result ids
 *
 * This is the table half of `Query`; the SQL a browse generates for the objects themselves is query
 * building and stays in the class.
 */
interface TmpBrowseRepositoryInterface
{
    /**
     * Drops the browses whose session is gone
     */
    public function collectGarbage(): void;

    /**
     * Stores a new browse for a session and returns its id, or `null` when the write failed
     */
    public function create(string $sessionId, string $data): ?int;

    /**
     * Reads the stored state and result ids of a browse
     *
     * @return array{data?: ?string, object_data?: ?string}
     */
    public function getRow(int $browseId, string $sessionId): array;

    /**
     * Stores the result ids of a browse
     */
    public function updateObjectData(int $browseId, string $sessionId, string $objectData): void;

    /**
     * Stores the state of a browse
     */
    public function updateState(int $browseId, string $sessionId, string $data): void;
}
