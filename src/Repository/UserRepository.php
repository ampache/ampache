<?php
/*
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
     * Lookup for a user id with a certain name
     */
    public function idByUsername(string $username): int
    {
        if ($username == '-1') {
            return 0;
        }
        $db_results = Dba::read(
            'SELECT `id` FROM `user` WHERE `username`= ?',
            [$username]
        );

        $data   = Dba::fetch_assoc($db_results);
        $result = $data['id'] ?? null;

        if ($result !== null) {
            return (int) $result;
        }

        return 0;
    }

    /**
     * Lookup for a user id with a certain email
     */
    public function idByEmail(string $email): int
    {
        $db_results = Dba::read(
            'SELECT `id` FROM `user` WHERE `email`= ?',
            [$email]
        );

        $data   = Dba::fetch_assoc($db_results);
        $result = $data['id'] ?? null;

        if ($result !== null) {
            return (int) $result;
        }

        return 0;
    }

    /**
     * Look up a user id by reset token (DOES NOT FIND ADMIN USERS)
     */
    public function idByResetToken(string $token): int
    {
        $sql        = 'SELECT `id`, `username`, `email` FROM `user` WHERE `access` != 100;';
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $email_hash = hash('sha256', $row['email']);
            $user_token = hash('sha256', $row['username'] . $email_hash);
            if ($token === $user_token) {
                return (int)$row['id'];
            }
        }

        return 0;
    }
    /**
     * This returns all valid users in database.
     *
     * @return int[]
     */
    public function getValid(bool $includeDisabled = false): array
    {
        $key   = 'users';
        $value = ($includeDisabled)
            ? 'users_all'
            : 'users_valid';
        if (User::is_cached($key, $value)) {
            return User::get_from_cache($key, $value);
        }
        $users = array();
        $sql   = ($includeDisabled)
            ? 'SELECT `id` FROM `user`;'
            : 'SELECT `id` FROM `user` WHERE `disabled` = \'0\';';

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = (int) $results['id'];
        }
        User::add_to_cache($key, $value, $users);

        return $users;
    }

    /**
     * This returns all valid users in an array (id => name).
     */
    public function getValidArray(bool $includeDisabled = false): array
    {
        $key   = 'users';
        $value = ($includeDisabled)
            ? 'userarray_all'
            : 'userarray_valid';
        if (User::is_cached($key, $value)) {
            return User::get_from_cache($key, $value);
        }
        $users = array();
        $sql   = ($includeDisabled)
            ? 'SELECT `id`, `username` FROM `user`;'
            : 'SELECT `id`, `username` FROM `user` WHERE `disabled` = \'0\';';

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[(int) $results['id']] = $results['username'];
        }
        User::add_to_cache($key, $value, $users);

        return $users;
    }

    /**
     * Remove details for users that no longer exist.
     */
    public function collectGarbage(): void
    {
        // simple deletion queries.
        $user_tables = array(
            'access_list',
            'bookmark',
            'broadcast',
            'democratic',
            'ip_history',
            'object_count',
            'playlist',
            'rating',
            'search',
            'share',
            'tag_map',
            'user_activity',
            'user_data',
            'user_flag',
            'user_preference',
            'user_shout',
            'user_vote',
            'wanted'
        );
        foreach ($user_tables as $table_id) {
            $sql = "DELETE FROM `" . $table_id . "` WHERE `user` IS NOT NULL AND `user` != -1 AND `user` != 0 AND `user` NOT IN (SELECT `id` FROM `user`);";
            Dba::write($sql);
        }
        // reset their data to null if they've made custom changes
        $user_tables = array(
            'artist',
            'label'
        );
        foreach ($user_tables as $table_id) {
            $sql = "UPDATE `" . $table_id . "` SET `user` = NULL WHERE `user` IS NOT NULL AND `user` != -1 AND `user` NOT IN (SELECT `id` FROM `user`);";
            Dba::write($sql);
        }

        // Clean up the playlist data table
        $sql = "DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `playlist` ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist`.`id` IS NULL";
        Dba::write($sql);

        // Clean out the tags
        $sql = "DELETE FROM `tag` WHERE `tag`.`id` NOT IN (SELECT `tag_id` FROM `tag_map`) AND `tag`.`id` NOT IN (SELECT `tag_id` FROM `tag_merge`)";
        Dba::write($sql);

        // Clean out the tag_merges that have been lost
        $sql = "DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` NOT IN (SELECT `id` FROM `tag`) OR `tag_merge`.`merged_to` NOT IN (SELECT `id` FROM `tag`)";
        Dba::write($sql);

        // Delete their following/followers
        $sql = "DELETE FROM `user_follower` WHERE (`user` NOT IN (SELECT `id` FROM `user`)) OR (`follow_user` NOT IN (SELECT `id` FROM `user`))";
        Dba::write($sql);

        $sql = "DELETE FROM `session` WHERE `username` IS NOT NULL AND `username` NOT IN (SELECT `username` FROM `user`);";
        Dba::write($sql);
    }

    /**
     * This returns a built user from a username
     */
    public function findByUsername(string $username): ?User
    {
        $user       = null;
        $sql        = 'SELECT `id` FROM `user` WHERE `username` = ?';
        $db_results = Dba::read($sql, array($username));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user = new User((int) $results['id']);
        }

        return $user;
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

            if (array_key_exists('id', $results)) {
                return new User((int) $results['id']);
            }
            // check for api sessions
            $sql        = "SELECT `username` FROM `session` WHERE `id` = ? AND `expire` > ? AND type = 'api'";
            $db_results = Dba::read($sql, array($apikey, time()));
            $results    = Dba::fetch_assoc($db_results);

            if (array_key_exists('username', $results)) {
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
     * This returns a built user from a streamToken
     */
    public function findByStreamToken(string $streamToken): ?User
    {
        if (!empty($streamToken)) {
            // check for legacy unencrypted streamtoken
            $sql        = "SELECT `id` FROM `user` WHERE `streamtoken` = ?";
            $db_results = Dba::read($sql, array($streamToken));
            $results    = Dba::fetch_assoc($db_results);

            if (array_key_exists('id', $results)) {
                return new User((int) $results['id']);
            }
            // check for sha256 hashed streamtoken for client
            // https://ampache.org/api/
            $sql        = "SELECT `id`, `streamtoken`, `username` FROM `user`";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                if ($row['streamtoken'] && $row['username']) {
                    $key        = hash('sha256', $row['streamtoken']);
                    $passphrase = hash('sha256', $row['username'] . $key);
                    if ($passphrase == $streamToken) {
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
     * Updates a users Stream token
     */
    public function updateStreamToken(int $userId, string $userName, string $streamToken): void
    {
        $sql = "UPDATE `user` SET `streamtoken` = ? WHERE `id` = ?";
        Dba::write($sql, array($streamToken, $userId));
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

    /**
     * Returns statistical data related to user accounts and active users
     *
     * @param int $timePeriod Time period to consider sessions `active` (in seconds)
     *
     * @return array{users: int, connected: int}
     */
    public function getStatistics(int $timePeriod = 1200): array
    {
        $userResult = Dba::fetch_single_column(
            'SELECT COUNT(`id`) FROM `user`'
        );

        $time = time();

        $sessionResult = Dba::fetch_single_column(
            <<<SQL
                SELECT
                COUNT(DISTINCT `session`.`username`)
                FROM `session`
                INNER JOIN `user`
                ON `session`.`username` = `user`.`username`
                WHERE `session`.`expire` > ? AND `user`.`last_seen` > ?,
            SQL,
            [$time, $time - $timePeriod]
        );

        return [
            'users' => (int) $userResult,
            'connected' => (int) $sessionResult,
        ];
    }
}
