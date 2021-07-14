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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Repository\Model\User;
use Ampache\Module\System\Dba;

final class UserRepository implements UserRepositoryInterface
{
    /**
     * This returns a built user from a rsstoken
     */
    public function getByRssToken(string $rssToken): ?User
    {
        $user       = null;
        $sql        = "SELECT `id` FROM `user` WHERE `rsstoken` = ?";
        $db_results = Dba::read($sql, array($rssToken));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user = new User((int) $results['id']);
        }

        return $user;
    }

    /**
     * Lookup for a user with a certain name
     */
    public function findByUsername(string $username): ?int
    {
        $db_results = Dba::read(
            'SELECT `id` FROM `user` WHERE `username`= ?',
            [$username]
        );

        $data   = Dba::fetch_assoc($db_results);
        $result = $data['id'] ?? null;

        if ($result !== null) {
            return (int) $result;
        }

        return null;
    }

    /**
     * This returns all valid users in database.
     *
     * @return int[]
     */
    public function getValid(bool $includeDisabled = false): array
    {
        $users = array();
        $sql   = ($includeDisabled)
            ? 'SELECT `id` FROM `user`'
            : 'SELECT `id` FROM `user` WHERE `disabled` = \'0\'';

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = (int) $results['id'];
        }

        return $users;
    }

    /**
     * This returns all valid users in an array (id => name).
     *
     * @param bool $includeDisabled
     * @return array
     */
    public function getValidArray(bool $includeDisabled = false): array
    {
        $users = array();
        $sql   = ($includeDisabled)
            ? 'SELECT `id`, `username` FROM `user`'
            : 'SELECT `id`, `username` FROM `user` WHERE `disabled` = \'0\'';

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[(int) $results['id']] = $results['username'];
        }

        return $users;
    }

    /**
     * This returns username for an id (or an empty string)
     */
    public function getUsernameById(int $user_id): string
    {
        $db_results = Dba::read(
            'SELECT `username` FROM `user` WHERE `id`= ?',
            [$user_id]
        );

        $data = Dba::fetch_assoc($db_results);

        return (string)$data['username'];
    }

    /**
     * This returns a built user from a email
     */
    public function findByEmail(string $email): ?User
    {
        $user       = null;
        $sql        = 'SELECT `id` FROM `user` WHERE `email` = ?';
        $db_results = Dba::read($sql, array($email));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user = new User((int) $results['id']);
        }

        return $user;
    }

    /**
     * This returns users list related to a website.
     *
     * @return int[]
     *
     * @todo rework. the query limits the results to 1, so it doesn't need to return an array
     */
    public function findByWebsite(string $website): array
    {
        $website    = rtrim((string)$website, "/");
        $sql        = 'SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1';
        $db_results = Dba::read($sql, array($website));
        $users      = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = (int) $results['id'];
        }

        return $users;
    }

    /**
     * This returns a built user from an apikey
     */
    public function findByApiKey(string $apikey): ?User
    {
        if (!empty($apikey)) {
            // check for legacy unencrypted apikey
            $sql        = "SELECT `id` FROM `user` WHERE `apikey` = ?";
            $db_results = Dba::read($sql, array($apikey));
            $results    = Dba::fetch_assoc($db_results);

            if ($results['id']) {
                return new User((int) $results['id']);
            }
            // check for api sessions
            $sql        = "SELECT `username` FROM `session` WHERE `id` = ? AND `expire` > ? AND type = 'api'";
            $db_results = Dba::read($sql, array($apikey, time()));
            $results    = Dba::fetch_assoc($db_results);

            if ($results['username']) {
                return User::get_from_username($results['username']);
            }
            // check for sha256 hashed apikey for client
            // https://ampache.org/api/
            $sql        = "SELECT `id`, `apikey`, `username` FROM `user`";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                if ($row['apikey'] && $row['username']) {
                    $key        = hash('sha256', $row['apikey']);
                    $passphrase = hash('sha256', $row['username'] . $key);
                    if ($passphrase == $apikey) {
                        return new User((int) $row['id']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * updates the last seen data for the user
     */
    public function updateLastSeen(
        int $userId
    ): void {
        Dba::write(
            'UPDATE user SET last_seen = ? WHERE `id` = ?',
            [time(), $userId]
        );
    }

    /**
     * this enables the user
     */
    public function enable(int $userId): void
    {
        Dba::write(
            'UPDATE `user` SET `disabled`=\'0\' WHERE id = ?',
            [$userId]
        );
    }

    /**
     * Retrieve the validation code of a certain user by its username
     */
    public function getValidationByUsername(string $username): ?string
    {
        $sql        = "SELECT `validation` FROM `user` WHERE `username` = ?";
        $db_results = Dba::read($sql, [$username]);

        $row = Dba::fetch_assoc($db_results);

        return $row['validation'] ?? null;
    }

    /**
     * Activates the user by username
     */
    public function activateByUsername(string $username): void
    {
        $sql = "UPDATE `user` SET `disabled`='0' WHERE `username` = ?";
        Dba::write($sql, [$username]);
    }

    /**
     * Updates a users RSS token
     */
    public function updateRssToken(int $userId, string $rssToken): void
    {
        $sql = "UPDATE `user` SET `rsstoken` = ? WHERE `id` = ?";

        Dba::write($sql, array($rssToken, $userId));
    }

    /**
     * Updates a users api key
     */
    public function updateApiKey(string $userId, string $apikey): void
    {
        $sql = "UPDATE `user` SET `apikey` = ? WHERE `id` = ?";

        Dba::write($sql, array($apikey, $userId));
    }

    /**
     * Get the current hashed user password
     */
    public function retrievePasswordFromUser(int $userId): string
    {
        $sql        = 'SELECT * FROM `user` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($userId));
        $row        = Dba::fetch_assoc($db_results);

        return $row['password'] ?? '';
    }
}
