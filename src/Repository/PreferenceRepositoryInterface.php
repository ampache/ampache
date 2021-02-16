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
 */

namespace Ampache\Repository;

interface PreferenceRepositoryInterface
{
    /**
     * This takes a name and returns the id
     */
    public function getIdByName(string $name): int;

    /**
     * This returns a nice flat array of all of the possible preferences for the specified user
     *
     * @return array<array<string, mixed>>
     */
    public function getAll(int $userId): array;

    /**
     * This returns a nice flat array of all of the possible preferences for the specified user
     *
     * @return array<array<string, mixed>>
     */
    public function get(string $preferenceName, int $userId): array;

    /**
     * This deletes the specified preference by id
     */
    public function deleteById(int $preferenceId): void;

    /**
     * This deletes the specified preference by name
     */
    public function deleteByName(string $preferenceName): void;

    /**
     * This removes any garbage
     */
    public function cleanPreferences(): void;
}
