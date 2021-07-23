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

use Ampache\Repository\Model\User;

interface UserRepositoryInterface
{
    /**
     * This returns a built user from a rsstoken
     */
    public function getByRssToken(string $rssToken): ?User;

    /**
     * Lookup for a user with a certain name
     */
    public function findByUsername(string $username): ?int;

    /**
     * This returns all valid users in database.
     *
     * @return int[]
     */
    public function getValid(bool $includeDisabled = false): array;

    /**
     * This returns all valid users in an array (id => name).
     *
     * @param bool $includeDisabled
     * @return array
     */
    public function getValidArray(bool $includeDisabled = false): array;

    /**
     * This returns username for an id (or an empty string)
     */
    public function getUsernameById(int $user_id): string;

    /**
     * This returns a built user from a email
     */
    public function findByEmail(string $email): ?User;

    /**
     * This returns users list related to a website.
     *
     * @return int[]
     *
     * @todo rework. the query limits the results to 1, so it doesn't need to return an array
     */
    public function findByWebsite(string $website): array;

    /**
     * This returns a built user from an apikey
     */
    public function findByApiKey(string $apikey): ?User;

    /**
     * updates the last seen data for this user
     */
    public function updateLastSeen(
        int $userId
    ): void;

    /**
     * this enables the user
     */
    public function enable(int $userId): void;

    /**
     * Retrieve the validation code of a certain user by its username
     */
    public function getValidationByUsername(string $username): ?string;

    /**
     * Activates the user by username
     */
    public function activateByUsername(string $username): void;

    /**
     * Updates a users RSS token
     */
    public function updateRssToken(int $userId, string $rssToken): void;


    /**
     * Updates a users api key
     */
    public function updateApiKey(string $userId, string $apikey): void;

    /**
     * Get the current hashed user password
     */
    public function retrievePasswordFromUser(int $userId): string;
}
