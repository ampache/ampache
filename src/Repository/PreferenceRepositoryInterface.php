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
     * Adds a preference a user is missing; duplicates are ignored so the caller can be optimistic
     */
    public function addUserPreference(int $userId, int $preferenceId, string $name, int|string|null $value): void
    ;

    /**
     * Drops the user rows whose preference no longer exists
     */
    public function collectGarbage(): void;

    /**
     * Drops the preference rows that no longer belong to anyone, and the system ones that leaked onto users
     */
    public function collectPreferenceGarbage(): void
    ;

    /**
     * Copies the server's own preference values onto a user
     */
    public function copySystemPreferences(int $userId): bool;

    /**
     * Counts the preferences matching a name or an id, which is how existence is asked
     */
    public function countByNameOrId(int|string $preference): int;

    /**
     * Drops a preference by name or by id
     */
    public function deleteByNameOrId(int|string $preference): bool;

    /**
     * Drops one duplicated preference row, matching on the value so the surviving copy is the one kept
     */
    public function deleteDuplicatePreference(int $userId, int $preferenceId, int|string|null $value): void
    ;

    /**
     * Reads the id of a preference by name
     */
    public function findIdByName(string $name): ?int;

    /**
     * Reads the names from a list that have no `preference` row yet
     *
     * @param list<string> $names
     * @return list<string>
     */
    public function findMissingNames(array $names): array;

    /**
     * Reads the name of a preference by id
     */
    public function findNameById(int|string $preferenceId): ?string;

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
     * Reads the categories preferences are grouped under
     *
     * @return list<string>
     */
    public function getCategories(): array;

    /**
     * Reads the users holding fewer preferences than exist, which is the cheap way to find the ones needing repair
     *
     * @return list<int>
     */
    public function getIdsMissingPreferences(): array
    ;

    /**
     * Reads the name and value of every preference resolved for a user
     *
     * @return list<array<string, mixed>>
     */
    public function getInitRows(int $userId): array;

    /**
     * Reads the access level a preference demands
     */
    public function getLevel(string $name): ?int;

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
     * Reads one preference as a user sees it, with the row the display needs
     *
     * Pass true for a real user, whose own row never applies to a system-category preference
     *
     * @return array<string, mixed>
     */
    public function getUserPreferenceRow(string $name, int $userId, bool $excludeSystem): array;

    /**
     * Reads a user's stored values, keyed by whichever column this schema carries
     *
     * @return array<int|string, ?string>
     */
    public function getUserValues(int $userId, bool $keyedByName): array;

    /**
     * Whether this database spells the column `category`, which it has since Migration600051
     */
    public function hasCategoryColumn(): bool;

    /**
     * Whether `user_preference` carries the name column it gained in Migration700020
     */
    public function hasUserPreferenceName(): bool;

    /**
     * Inserts one preference with the row Ampache ships for it
     */
    public function insertDefault(
        string $name,
        string $value,
        string $description,
        int $level,
        string $type,
        string $category,
        ?string $subcategory,
    ): bool;

    /**
     * Inserts a preference and seeds it onto the server and, unless it is a system one, onto every user
     */
    public function insertPreference(
        string $name,
        string $description,
        float|int|string|null $default,
        int $level,
        string $type,
        string $category,
        ?string $subcategory,
    ): bool;

    /**
     * Renames a preference
     */
    public function rename(string $oldName, string $newName): void;

    /**
     * Resets any `lang` preference that names a locale Ampache does not ship, so the UI does not fall over
     *
     * The system user is repaired first and its value becomes the fallback for everyone else.
     */
    public function repairLanguagePreferences(): void
    ;

    /**
     * Puts every preference on one access level
     */
    public function setAllLevels(int $level): bool;

    /**
     * Puts named preferences on the access level they are shipped with
     *
     * @param array<int, list<string>> $levels level => the preferences taking it
     */
    public function setLevels(array $levels): bool;

    /**
     * Writes a preset onto a user, one statement per distinct value
     *
     * @param array<int|string, list<string>> $values value => the preferences taking it
     */
    public function setUserPreferenceValues(int $userId, array $values): bool;

    /**
     * Applies the canonical description of every preference, leaving the ones already correct alone
     *
     * @param array<string, string> $descriptions name => description
     */
    public function updateDescriptions(array $descriptions): void;

    /**
     * Puts one preference on an access level
     */
    public function updateLevel(int|string $preferenceId, int $level): void;

    /**
     * Writes one value, optionally onto the shipped default and onto every user rather than just one
     */
    public function updateValue(int|string $preference, bool|float|int|string|null $value, ?int $userId, bool $applyToDefault): void;

    /**
     * Writes one value for every user, which is what an admin changing a default means
     */
    public function updateValueForAll(int|string $preference, bool|float|int|string|null $value): void;
}
