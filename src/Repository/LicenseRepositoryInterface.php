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
 */

namespace Ampache\Repository;

interface LicenseRepositoryInterface
{
    /**
     * Returns a list of licenses accessible by the current user.
     *
     * @return int[]
     */
    public function getAll(): array;

    /**
     * This inserts a new license entry, it returns the auto_inc id
     *
     * @return int The id of the created license
     */
    public function create(
        string $name,
        string $description,
        string $externalLink
    ): int;

    /**
     * This takes a key'd array of data as input and updates a license entry
     */
    public function update(
        int $licenseId,
        string $name,
        string $description,
        string $externalLink
    ): void;

    /**
     * Deletes the license
     */
    public function delete(
        int $licenseId
    ): void;

    /**
     * Searches for the License by name and external link
     */
    public function find(string $searchValue): ?int;
}
