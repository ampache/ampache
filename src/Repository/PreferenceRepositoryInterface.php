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

use Ampache\Repository\Model\User;

/**
 * Provides access to the `preference` table
 */
interface PreferenceRepositoryInterface
{
    /**
     * Adds every catalog the default filter group is missing, so a new catalog is visible without a manual edit
     */
    public function addMissingCatalogsToDefaultFilterGroup(): void
    ;

    /**
     * Adds a preference a user is missing; duplicates are ignored so the caller can be optimistic
     */
    public function addUserPreference(int $userId, int $preferenceId, string $name, int|string|null $value): void
    ;

    /**
     * Drops the preference rows that no longer belong to anyone, and the system ones that leaked onto users
     */
    public function collectPreferenceGarbage(): void
    ;

    /**
     * Drops one duplicated preference row, matching on the value so the surviving copy is the one kept
     */
    public function deleteDuplicatePreference(int $userId, int $preferenceId, int|string|null $value): void
    ;

    /**
     * Returns a nice flat dict of all the possible preferences
     *
     * If no user is provided, all available system-wide preferences will be returned
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     value: string,
     *     description: string,
     *     level: int,
     *     type: string,
     *     category: string,
     *     subcategory: ?string,
     *     has_access?: bool,
     *     values?: string[]|int[],
     * }>
     */
    public function getAll(
        ?User $user = null,
        ?bool $api = false,
    ): array;

    /**
     * Reads every known preference, dropping the system-only ones when the target is a real user
     *
     * @return list<array{id: int, name: string, value: ?string}>
     */
    public function getAllPreferences(bool $includeSystem): array
    ;

    /**
     * Reads the users holding fewer preferences than exist, which is the cheap way to find the ones needing repair
     *
     * @return list<int>
     */
    public function getIdsMissingPreferences(): array
    ;

    /**
     * Reads a user's stored preferences as preference-id => value, so duplicates are visible to the caller
     *
     * @return list<array{preference: int, value: ?string}>
     */
    public function getStoredPreferences(int $userId): array
    ;

    /**
     * Reads the system user's non-plugin preferences, which seed the values a new user starts with
     *
     * @return list<array{preference: int, name: string, value: ?string}>
     */
    public function getSystemDefaultPreferences(): array
    ;

    /**
     * Puts the DEFAULT catalog filter group back at id 0, where the rest of the schema assumes it lives
     *
     * Autoincrement starts at 1, so a group inserted normally lands in the wrong place and every catalog filter
     * silently stops matching. Returns whether the repair had to run.
     */
    public function repairDefaultFilterGroup(): bool
    ;

    /**
     * Resets any `lang` preference that names a locale Ampache does not ship, so the UI does not fall over
     *
     * The system user is repaired first and its value becomes the fallback for everyone else.
     */
    public function repairLanguagePreferences(): void
    ;
}
