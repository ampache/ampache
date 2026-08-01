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
use Ampache\Repository\Model\UserFieldEnum;

interface UserRepositoryInterface
{
    /**
     * Activates the user by username
     */
    public function activateByUsername(string $username): void;

    /**
     * Remove details for users that no longer exist.
     */
    public function collectGarbage(): void;

    /**
     * Counts the album disks reachable through a set of catalogs, which has no plain per-table equivalent
     *
     * @param array<int> $catalogIds
     */
    public function countAlbumDisksForCatalogs(array $catalogIds): int;

    /**
     * Counts the users assigned to a catalog filter group
     */
    public function countByCatalogFilterGroup(int $groupId): int;

    /**
     * Counts the rows of a table a user is allowed to see, honouring the catalog filter when one applies
     */
    public function countForUser(string $table, int $userId, bool $filtered): int;

    /**
     * Inserts a new user row and returns its id, or 0 when the write failed
     *
     * @param array<string, mixed> $columns Column name => value; the optional ones are simply absent
     */
    public function create(array $columns): int;

    /**
     * Removes a user along with its custom access rules and any session it left behind
     */
    public function delete(int $userId, string $userName): void;

    /**
     * Drops every session a user holds, logging them out everywhere
     */
    public function deleteSessions(string $userName): void;

    /**
     * Marks a user disabled, without touching their access level
     */
    public function disableUser(int $userId): void;

    /**
     * this enables the user
     */
    public function enable(int $userId): void;

    /**
     * Returns the IP of a live session for this user, or null when they are not logged in anywhere
     */
    public function findActiveSessionIp(string $userName, int $now, bool $perpetualApiSession): ?string;

    /**
     * This returns a built user from an apikey
     */
    public function findByApiKey(string $apikey): ?User;

    /**
     * This returns a built user from a email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Finds a user by its id
     */
    public function findById(int $id): ?User;

    /**
     * This returns a built user from a streamToken
     */
    public function findByStreamToken(string $streamToken): ?User;

    /**
     * This returns a built user from a username
     */
    public function findByUsername(string $username): ?User;

    /**
     * This returns users list related to a website.
     *
     * @return int[]
     *
     * @todo rework. the query limits the results to 1, so it doesn't need to return an array
     */
    public function findByWebsite(string $website): array;

    /**
     * Clears the validation key of everyone who has since managed to log in
     */
    public function garbageCollectUnvalidated(): void;

    /**
     * Reads every user id, for the sweeps that have to touch all of them
     *
     * @return list<int>
     */
    public function getAllIds(): array;

    /**
     * This returns a built user from a rsstoken
     */
    public function getByRssToken(string $rssToken): ?User;

    /**
     * Sums the count, playtime and megabytes of one media table across a set of catalogs
     *
     * @param array<int> $catalogIds
     * @return array{count: int, time: int, size: int}
     */
    public function getMediaTotals(string $table, array $catalogIds, bool $enabledOnly): array;

    /**
     * Reads the playlists a user owns
     *
     * @return list<int>
     */
    public function getPlaylistIds(int $userId, bool $includePrivate): array;

    /**
     * Sums the megabytes a user has streamed, across the live counts and the summarised ones
     */
    public function getPlaySize(int $userId): int;

    /**
     * Reads the preference rows behind the settings pages, joined to their descriptions
     *
     * @return list<array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: ?string}>
     */
    public function getPreferenceRows(int $userId, ?string $category, bool $excludeSystem): array;

    /**
     * Reads the non-system preference name/value pairs that get loaded into the session
     *
     * @return list<array{name: string, value: ?string}>
     */
    public function getPreferenceValues(int $userId): array;

    /**
     * Reads the objects a user played most recently, or least recently when asked for the oldest
     *
     * @return list<int>
     */
    public function getRecentlyPlayed(int $userId, string $objectType, string $countType, int $count, int $offset, bool $newest): array;

    /**
     * Reads the whole user row the model hydrates itself from
     *
     * @return array<string, mixed>|null
     */
    public function getRow(int $userId): ?array;

    /**
     * Returns statistical data related to user accounts and active users
     *
     * @param int $timePeriod Time period to consider sessions `active` (in seconds)
     * @return array{users: int, connected: int}
     */
    public function getStatistics(int $timePeriod = 1200): array;

    /**
     * Reads the free-form counters kept against a user, optionally narrowed to one key
     *
     * @return array<string, string>
     */
    public function getUserData(int $userId, ?string $key): array;

    /**
     * This returns all valid users in database.
     *
     * @return int[]
     */
    public function getValid(bool $includeDisabled = false): array;

    /**
     * This returns all valid users in an array (id => name).
     *
     * @return string[]
     */
    public function getValidArray(bool $includeDisabled = false): array;

    /**
     * Retrieve the validation code of a certain user by its username
     */
    public function getValidationByUsername(string $username): ?string;

    /**
     * Whether another admin account exists, so the caller can refuse to strip the last one
     */
    public function hasOtherAdmin(int $excludingUserId, bool $enabledOnly): bool;

    /**
     * Lookup for a user id with a certain email
     */
    public function idByEmail(string $email): int;

    /**
     * Look up a user id by reset token (DOES NOT FIND ADMIN USERS)
     */
    public function idByResetToken(string $token): int;

    /**
     * Lookup for a user id with a certain name
     */
    public function idByUsername(string $username): int;

    /**
     * Puts the users of one catalog filter group back on DEFAULT, after that group is deleted
     */
    public function resetCatalogFilterGroup(int $groupId): void;

    /**
     * Puts every user pointing at a catalog filter group that no longer exists back on DEFAULT
     */
    public function resetMissingCatalogFilterGroups(): void;

    /**
     * Get the current hashed user password
     */
    public function retrievePasswordFromUser(int $userId): string;

    /**
     * Writes a single user column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $userId, UserFieldEnum $field, int|string|null $value): bool;

    /**
     * Writes a free-form counter against a user, replacing whatever was there
     */
    public function setUserData(int $userId, string $key, float|int|string $value): void;

    /**
     * Writes a fresh validation key and disables the account until it is used
     */
    public function setValidation(int $userId, string $validation): bool;

    /**
     * Updates a users api key
     */
    public function updateApiKey(int $userId, string $apikey): void;

    /**
     * updates the last seen data for this user
     */
    public function updateLastSeen(
        int $userId,
    ): void;

    /**
     * Updates a users RSS token
     */
    public function updateRssToken(int $userId, string $rssToken): void;

    /**
     * Updates a users Stream token
     */
    public function updateStreamToken(int $userId, string $userName, string $streamToken): void;

    /**
     * Stores the encrypted Subsonic secret for a user, or clears it when `null` is given
     */
    public function updateSubsonicSecret(int $userId, ?string $secret): void;
}
