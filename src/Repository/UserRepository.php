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

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Dba;
use Doctrine\DBAL\Connection;

final class UserRepository implements UserRepositoryInterface
{
    private Connection $database;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        Connection $database,
        ModelFactoryInterface $modelFactory
    ) {
        $this->database     = $database;
        $this->modelFactory = $modelFactory;
    }

    /**
     * This returns a built user from a rsstoken
     */
    public function findByRssToken(string $rssToken): ?User
    {
        $userId = $this->database->fetchOne(
            'SELECT `id` FROM `user` WHERE `rsstoken` = ?',
            [$rssToken]
        );

        if ($userId === false) {
            return null;
        }

        return $this->modelFactory->createUser((int) $userId);
    }

    /**
     * Lookup for a user with a certain name
     */
    public function findByUsername(string $username): ?int
    {
        $userId = $this->database->fetchOne(
            'SELECT `id` FROM `user` WHERE `username`= ?',
            [$username]
        );

        if ($userId === false) {
            return null;
        }

        return (int) $userId;
    }

    /**
     * This returns all valid users in database.
     *
     * @return int[]
     */
    public function getValid(bool $includeDisabled = false): array
    {
        $users = [];
        $sql   = $includeDisabled
            ? 'SELECT `id` FROM `user`'
            : 'SELECT `id` FROM `user` WHERE `disabled` = \'0\'';

        $dbResult = $this->database->executeQuery($sql);

        while ($userId = $dbResult->fetchOne()) {
            $users[] = (int) $userId;
        }

        return $users;
    }

    /**
     * This returns a built user from a email
     */
    public function findByEmail(string $email): ?User
    {
        $userId = $this->database->fetchOne(
            'SELECT `id` FROM `user` WHERE `email` = ?',
            [$email]
        );

        if ($userId === false) {
            return null;
        }

        return $this->modelFactory->createUser((int) $userId);
    }

    /**
     * This returns a user related to a website.
     */
    public function findByWebsite(string $website): ?User
    {
        $userId = $this->database->fetchOne(
            'SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1',
            [
                rtrim($website, '/')
            ]
        );

        if ($userId === false) {
            return null;
        }

        return $this->modelFactory->createUser((int) $userId);
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
        int $userId,
        int $time
    ): void {
        $this->database->executeQuery(
            'UPDATE user SET last_seen = ? WHERE `id` = ?',
            [$time, $userId]
        );
    }

    /**
     * this enables the user
     */
    public function enable(int $userId): void
    {
        $this->database->executeQuery(
            'UPDATE `user` SET `disabled` = \'0\' WHERE id = ?',
            [$userId]
        );
    }

    /**
     * Retrieve the validation code of a certain user by its username
     */
    public function getValidationByUsername(string $username): ?string
    {
        $validation = $this->database->fetchOne(
            'SELECT `validation` FROM `user` WHERE `username` = ?',
            [$username]
        );

        if ($validation === false) {
            return null;
        }

        return $validation;
    }

    /**
     * Activates the user by username
     */
    public function activateByUsername(string $username): void
    {
        $this->database->executeQuery(
            'UPDATE `user` SET `disabled` = \'0\' WHERE `username` = ?',
            [$username]
        );
    }

    /**
     * Updates a users RSS token
     */
    public function updateRssToken(int $userId, string $rssToken): void
    {
        $this->database->executeQuery(
            'UPDATE `user` SET `rsstoken` = ? WHERE `id` = ?',
            [$rssToken, $userId]
        );
    }

    /**
     * Updates a users api key
     */
    public function updateApiKey(int $userId, string $apiKey): void
    {
        $this->database->executeQuery(
            'UPDATE `user` SET `apikey` = ? WHERE `id` = ?',
            [$apiKey, $userId]
        );
    }

    /**
     * Get the current hashed user password
     */
    public function retrievePasswordFromUser(int $userId): string
    {
        $password = $this->database->fetchOne(
            'SELECT password FROM `user` WHERE `id` = ?',
            [$userId]
        );

        if ($password === false) {
            return '';
        }

        return $password;
    }

    /**
     * Inserts a new user into the database
     */
    public function create(
        string $username,
        string $fullname,
        string $email,
        string $website,
        string $password,
        int $access,
        string $state,
        string $city,
        bool $disabled
    ): ?int {
        $disabled = $disabled ? 1 : 0;

        /* Now Insert this new user */
        $sql    = 'INSERT INTO `user` (`username`, `disabled`, `fullname`, `email`, `password`, `access`, `create_date`';
        $params = [$username, $disabled, $fullname, $email, $password, $access, time()];

        if (!empty($website)) {
            $sql .= ", `website`";
            $params[] = $website;
        }
        if (!empty($state)) {
            $sql .= ", `state`";
            $params[] = $state;
        }
        if (!empty($city)) {
            $sql .= ", `city`";
            $params[] = $city;
        }

        $sql .= ") VALUES(?, ?, ?, ?, ?, ?, ?";

        if (!empty($website)) {
            $sql .= ", ?";
        }
        if (!empty($state)) {
            $sql .= ", ?";
        }
        if (!empty($city)) {
            $sql .= ", ?";
        }

        $sql .= ")";
        $this->database->executeQuery(
            $sql,
            $params
        );

        // Get the insert_id
        return (int) $this->database->lastInsertId();
    }
}
