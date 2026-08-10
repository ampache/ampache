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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\UserFieldEnum;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * Activates the user by username
     */
    public function activateByUsername(string $username): void
    {
        $this->connection->query(
            'UPDATE `user` SET `disabled` = 0, `validation` = NULL WHERE `username` = ?',
            [$username]
        );
    }

    /**
     * Remove details for users that no longer exist.
     */
    public function collectGarbage(): void
    {
        // simple deletion queries.
        $user_tables = [
            'access_list',
            'bookmark',
            'broadcast',
            'democratic',
            'ip_history',
            'object_count',
            'object_count_archive',
            'object_count_summary',
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
            'wanted',
        ];
        $statements = [];
        foreach ($user_tables as $table_id) {
            $statements[] = "DELETE FROM `" . $table_id . "` WHERE `user` IS NOT NULL AND `user` != -1 AND `user` != 0 AND `user` NOT IN (SELECT `id` FROM `user`);";
        }

        // the collaborator map names its column `user_id`, so the loop above steps over it entirely
        $statements[] = 'DELETE FROM `user_playlist_map` WHERE `user_id` NOT IN (SELECT `id` FROM `user`);';

        // reset their data to null if they've made custom changes
        $user_tables = [
            'artist',
            'label',
        ];
        foreach ($user_tables as $table_id) {
            $statements[] = "UPDATE `" . $table_id . "` SET `user` = NULL WHERE `user` IS NOT NULL AND `user` != -1 AND `user` NOT IN (SELECT `id` FROM `user`);";
        }

        $statements[] = 'UPDATE `song` SET `user_upload` = NULL WHERE `user_upload` IS NOT NULL AND `user_upload` != -1 AND `user_upload` NOT IN (SELECT `id` FROM `user`);';
        // Clean up the playlist data table
        $statements[] = 'DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `playlist` ON `playlist`.`id`=`playlist_data`.`playlist` WHERE `playlist`.`id` IS NULL';
        // Clean out the tags
        $statements[] = 'DELETE FROM `tag` WHERE `tag`.`id` NOT IN (SELECT `tag_id` FROM `tag_map`) AND `tag`.`id` NOT IN (SELECT `tag_id` FROM `tag_merge`)';
        // Clean out the tag_merges that have been lost
        $statements[] = 'DELETE FROM `tag_merge` WHERE `tag_merge`.`tag_id` NOT IN (SELECT `id` FROM `tag`) OR `tag_merge`.`merged_to` NOT IN (SELECT `id` FROM `tag`)';
        // Delete their following/followers
        $statements[] = 'DELETE FROM `user_follower` WHERE (`user` NOT IN (SELECT `id` FROM `user`)) OR (`follow_user` NOT IN (SELECT `id` FROM `user`))';
        $statements[] = 'DELETE FROM `session` WHERE `username` IS NOT NULL AND `username` NOT IN (SELECT `username` FROM `user`);';

        // one table the install does not have must not take the rest of the sweep down with it
        foreach ($statements as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $sql,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * Counts the album disks reachable through a set of catalogs, which has no plain per-table equivalent
     *
     * @param array<int> $catalogIds
     */
    public function countAlbumDisksForCatalogs(array $catalogIds): int
    {
        $idList = implode(',', array_map('intval', $catalogIds));

        return (int) $this->connection->fetchOne(
            "SELECT COUNT(DISTINCT `album_disk`.`id`) AS `count` FROM `album_disk` LEFT JOIN `album` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`object_type` = 'album' AND `album`.`catalog` IN (" . $idList . ')'
        );
    }

    /**
     * Counts the users assigned to a catalog filter group
     */
    public function countByCatalogFilterGroup(int $groupId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(1) AS `count` FROM `user` WHERE `catalog_filter_group` = ?',
            [$groupId]
        );
    }

    /**
     * Counts the rows of a table a user is allowed to see, honouring the catalog filter when one applies
     */
    public function countForUser(string $table, int $userId, bool $filtered): int
    {
        // `search`, `user` and `license` have no catalog, so they are the same number for everybody
        $sql = ($filtered)
            ? sprintf('SELECT COUNT(`id`) FROM `%s` WHERE', $table) . Catalog::get_user_filter($table, $userId)
            : sprintf('SELECT COUNT(`id`) FROM `%s`', $table);

        return (int) $this->connection->fetchOne($sql);
    }

    /**
     * Inserts a new user row and returns its id, or 0 when the write failed
     *
     * @param array<string, mixed> $columns Column name => value; the optional ones are simply absent
     */
    public function create(array $columns): int
    {
        $names        = array_keys($columns);
        $placeholders = implode(', ', array_fill(0, count($names), '?'));

        try {
            $this->connection->query(
                sprintf(
                    'INSERT INTO `user` (%s) VALUES(%s)',
                    implode(', ', array_map(static fn(string $name): string => sprintf('`%s`', $name), $names)),
                    $placeholders
                ),
                array_values($columns)
            );
        } catch (DatabaseException) {
            // the caller reads 0 as "not created" and stops
            return 0;
        }

        return $this->connection->getLastInsertedId();
    }

    /**
     * Removes a user along with its custom access rules and any session it left behind
     */
    public function delete(int $userId, string $userName): void
    {
        $this->connection->query('DELETE FROM `user` WHERE `id` = ?', [$userId]);
        $this->connection->query('DELETE FROM `access_list` WHERE `user` = ?', [$userId]);
        $this->deleteSessions($userName);
    }

    /**
     * Drops every session a user holds, logging them out everywhere
     */
    public function deleteSessions(string $userName): void
    {
        $this->connection->query('DELETE FROM `session` WHERE `username` = ?', [$userName]);
    }

    /**
     * Marks a user disabled, without touching their access level
     */
    public function disableUser(int $userId): void
    {
        $this->connection->query("UPDATE `user` SET `disabled`='1' WHERE `id` = ?", [$userId]);
    }

    /**
     * this enables the user
     */
    public function enable(int $userId): void
    {
        $this->connection->query(
            'UPDATE `user` SET `disabled` = 0 WHERE `id` = ?',
            [$userId]
        );
    }

    /**
     * Returns the IP of a live session for this user, or null when they are not logged in anywhere
     */
    public function findActiveSessionIp(string $userName, int $now, bool $perpetualApiSession): ?string
    {
        // a perpetual api session never expires, so it has to be matched on type rather than on the expiry
        $sql = ($perpetualApiSession)
            ? "SELECT `ip` FROM `session` WHERE `username` = ? AND ((`expire` = 0 AND `type` = 'api') OR `expire` > ?);"
            : 'SELECT `ip` FROM `session` WHERE `username` = ? AND `expire` > ?;';

        $ip = $this->connection->fetchOne($sql, [$userName, $now]);

        return ($ip === false || $ip === null)
            ? null
            : (string) $ip;
    }

    /**
     * This returns a built user from an apikey
     */
    public function findByApiKey(string $apikey): ?User
    {
        if ($apikey !== '' && $apikey !== '0') {
            // check for legacy unencrypted apikey
            $userId = $this->connection->fetchOne(
                'SELECT `id` FROM `user` WHERE `apikey` = ?',
                [$apikey]
            );

            if ($userId !== false) {
                return new User((int) $userId);
            }

            // check for api sessions
            $sql = (AmpConfig::get('perpetual_api_session'))
                ? "SELECT `username` FROM `session` WHERE `id` = ? AND (`expire` = 0 OR `expire` > ?) AND `type` = 'api'"
                : "SELECT `username` FROM `session` WHERE `id` = ? AND `expire` > ? AND `type` = 'api'";
            $userName = $this->connection->fetchOne($sql, [$apikey, time()]);

            if ($userName !== false) {
                return User::get_from_username((string) $userName);
            }

            // check for sha256 hashed apikey for client
            // https://ampache.org/api/
            $dbResults = $this->connection->query('SELECT `id`, `apikey`, `username` FROM `user`');
            while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
                if ($row['apikey'] && $row['username']) {
                    $key        = hash('sha256', (string) $row['apikey']);
                    $passphrase = hash('sha256', $row['username'] . $key);
                    if ($passphrase === $apikey) {
                        return new User((int) $row['id']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * This returns a built user from a email
     */
    public function findByEmail(string $email): ?User
    {
        $userId = $this->connection->fetchOne(
            'SELECT `id` FROM `user` WHERE `email` = ?',
            [$email]
        );

        return ($userId === false)
            ? null
            : new User((int) $userId);
    }

    /**
     * Finds a user by its id
     */
    public function findById(int $id): ?User
    {
        $user = new User($id);
        if ($user->isNew()) {
            return null;
        }

        return $user;
    }

    /**
     * This returns a built user from a streamToken
     */
    public function findByStreamToken(string $streamToken): ?User
    {
        if ($streamToken !== '' && $streamToken !== '0') {
            // check for legacy unencrypted streamtoken
            $userId = $this->connection->fetchOne(
                'SELECT `id` FROM `user` WHERE `streamtoken` = ?',
                [$streamToken]
            );

            if ($userId !== false) {
                return new User((int) $userId);
            }

            // check for sha256 hashed streamtoken for client
            // https://ampache.org/api/
            $dbResults = $this->connection->query('SELECT `id`, `streamtoken`, `username` FROM `user`');
            while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
                if ($row['streamtoken'] && $row['username']) {
                    $key        = hash('sha256', (string) $row['streamtoken']);
                    $passphrase = hash('sha256', $row['username'] . $key);
                    if ($passphrase === $streamToken) {
                        return new User((int) $row['id']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * This returns a built user from a username
     */
    public function findByUsername(string $username): ?User
    {
        if ($username === '-1') {
            return new User(-1);
        }

        $userId = $this->connection->fetchOne(
            'SELECT `id` FROM `user` WHERE `username` = ?',
            [$username]
        );

        return ($userId === false)
            ? null
            : new User((int) $userId);
    }

    /**
     * Clears the validation key of everyone who has since managed to log in
     */
    public function garbageCollectUnvalidated(): void
    {
        // activated accounts can log in but might not have cleared validation
        $this->connection->query('UPDATE `user` SET `validation` = NULL WHERE `last_seen` > 0;');
        // then drop the accounts that were never activated at all, once they are more than a month old
        $this->connection->query('DELETE FROM `user` WHERE (`last_seen` = 0 OR `validation` IS NOT NULL) AND `create_date` < UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL -1 MONTH));');
    }

    /**
     * Reads every user id, for the sweeps that have to touch all of them
     *
     * @return list<int>
     */
    public function getAllIds(): array
    {
        $result = $this->connection->query('SELECT `id` FROM `user`');

        $userIds = [];
        while ($userId = $result->fetchColumn()) {
            $userIds[] = (int) $userId;
        }

        return $userIds;
    }

    /**
     * This returns a built user from a rsstoken
     */
    public function getByRssToken(string $rssToken): ?User
    {
        $userId = $this->connection->fetchOne(
            'SELECT `id` FROM `user` WHERE `rsstoken` = ?',
            [$rssToken]
        );

        return ($userId === false)
            ? null
            : new User((int) $userId);
    }

    /**
     * Sums the count, playtime and megabytes of one media table across a set of catalogs
     *
     * @param array<int> $catalogIds
     * @return array{count: int, time: int, size: int}
     */
    public function getMediaTotals(string $table, array $catalogIds, bool $enabledOnly): array
    {
        $idList = implode(',', array_map('intval', $catalogIds));

        $sql = ($enabledOnly)
            ? sprintf("SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` WHERE `catalog` IN (%s) AND `%s`.`enabled`='1';", $table, $idList, $table)
            : sprintf('SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`)/1024/1024, 0) FROM `%s` WHERE `catalog` IN (%s);', $table, $idList);

        $result = $this->connection->query($sql);
        $row    = $result->fetch(PDO::FETCH_NUM);

        return [
            'count' => (int) ($row[0] ?? 0),
            'time' => (int) ($row[1] ?? 0),
            'size' => (int) ($row[2] ?? 0),
        ];
    }

    /**
     * Reads the playlists a user owns
     *
     * @return list<int>
     */
    public function getPlaylistIds(int $userId, bool $includePrivate): array
    {
        $sql = ($includePrivate)
            ? 'SELECT `id` FROM `playlist` WHERE `user` = ? ORDER BY `name`;'
            : "SELECT `id` FROM `playlist` WHERE `user` = ? AND `type` = 'public' ORDER BY `name`;";

        $result = $this->connection->query($sql, [$userId]);

        $playlistIds = [];
        while ($playlistId = $result->fetchColumn()) {
            $playlistIds[] = (int) $playlistId;
        }

        return $playlistIds;
    }

    /**
     * Sums the megabytes a user has streamed, across the live counts and the summarised ones
     */
    public function getPlaySize(int $userId): int
    {
        $statements = [
            "SELECT (IFNULL(SUM(`size`)/1024/1024, 0) + (SELECT IFNULL(SUM(`song`.`size` * `object_count_summary`.`count`)/1024/1024, 0) FROM `object_count_summary` LEFT JOIN `song` ON `song`.`id` = `object_count_summary`.`object_id` WHERE `object_count_summary`.`object_type` = 'song' AND `object_count_summary`.`count_type` = 'stream' AND `object_count_summary`.`user` = ?)) AS `size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` AND `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = ?;",
            "SELECT (IFNULL(SUM(`size`)/1024/1024, 0) + (SELECT IFNULL(SUM(`video`.`size` * `object_count_summary`.`count`)/1024/1024, 0) FROM `object_count_summary` LEFT JOIN `video` ON `video`.`id` = `object_count_summary`.`object_id` WHERE `object_count_summary`.`object_type` = 'video' AND `object_count_summary`.`count_type` = 'stream' AND `object_count_summary`.`user` = ?)) AS `size` FROM `object_count` LEFT JOIN `video` ON `video`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'video' AND `object_count`.`user` = ?;",
            "SELECT (IFNULL(SUM(`size`)/1024/1024, 0) + (SELECT IFNULL(SUM(`podcast_episode`.`size` * `object_count_summary`.`count`)/1024/1024, 0) FROM `object_count_summary` LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `object_count_summary`.`object_id` WHERE `object_count_summary`.`object_type` = 'podcast_episode' AND `object_count_summary`.`count_type` = 'stream' AND `object_count_summary`.`user` = ?)) AS `size` FROM `object_count`LEFT JOIN `podcast_episode` ON `podcast_episode`.`id`=`object_count`.`object_id` AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`user` = ?;",
        ];

        $total = 0;
        foreach ($statements as $sql) {
            $total += (int) $this->connection->fetchOne($sql, [$userId, $userId]);
        }

        return $total;
    }

    /**
     * Reads the preference rows behind the settings pages, joined to their descriptions
     *
     * @return list<array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: ?string}>
     */
    public function getPreferenceRows(int $userId, ?string $category, bool $excludeSystem): array
    {
        $limit = '';
        if ($excludeSystem) {
            $limit = "AND `preference`.`category` != 'system'";
        } elseif ($category !== null) {
            $limit = 'AND `preference`.`category` = ?';
        }

        $params = ($limit === 'AND `preference`.`category` = ?')
            ? [$userId, $category]
            : [$userId];

        $result = $this->connection->query(
            'SELECT `preference`.`name`, `preference`.`description`, `preference`.`category`, `preference`.`subcategory`, `preference`.`type`, preference.level, user_preference.value FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` = ? ' . $limit . ' ORDER BY `preference`.`category`, `preference`.`subcategory`, `preference`.`description`',
            $params
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'name' => (string) $row['name'],
                'description' => (string) $row['description'],
                'category' => (string) $row['category'],
                'subcategory' => $row['subcategory'],
                'type' => (string) $row['type'],
                'level' => (int) $row['level'],
                'value' => $row['value'],
            ];
        }

        return $rows;
    }

    /**
     * Reads the non-system preference name/value pairs that get loaded into the session
     *
     * @return list<array{name: string, value: ?string}>
     */
    public function getPreferenceValues(int $userId): array
    {
        $result = $this->connection->query(
            "SELECT `preference`.`name`, `user_preference`.`value` FROM `preference`, `user_preference` WHERE `user_preference`.`user` = ? AND `user_preference`.`preference` = `preference`.`id` AND `preference`.`type` != 'system';",
            [$userId]
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'name' => (string) $row['name'],
                'value' => $row['value'],
            ];
        }

        return $rows;
    }

    /**
     * Reads the objects a user played most recently, or least recently when asked for the oldest
     *
     * @return list<int>
     */
    public function getRecentlyPlayed(int $userId, string $objectType, string $countType, int $count, int $offset, bool $newest): array
    {
        $order = ($newest) ? 'DESC' : 'ASC';
        $limit = ($offset < 1)
            ? sprintf('%d', $count)
            : sprintf('%d, %d', $offset, $count);

        $result = $this->connection->query(
            'SELECT `object_id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = ? AND `user` = ? AND `count_type` = ? GROUP BY `object_id` ORDER BY `date` ' . $order . ' LIMIT ' . $limit . ' ',
            [$objectType, $userId, $countType]
        );

        $objectIds = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $objectIds[] = (int) $row['object_id'];
        }

        return $objectIds;
    }

    /**
     * Reads the whole user row the model hydrates itself from
     *
     * @return array<string, mixed>|null
     */
    public function getRow(int $userId): ?array
    {
        $row = $this->connection->fetchRow(
            'SELECT `id`, `username`, `fullname`, `email`, `website`, `apikey`, `access`, `disabled`, `last_seen`, `create_date`, `validation`, `state`, `city`, `fullname_public`, `rsstoken`, `streamtoken`, `subsonic_secret`, `catalog_filter_group` FROM `user` WHERE `id` = ?;',
            [$userId]
        );

        return (is_array($row) && $row !== [])
            ? $row
            : null;
    }

    /**
     * Returns statistical data related to user accounts and active users
     *
     * @param int $timePeriod Time period to consider sessions `active` (in seconds)
     * @return array{users: int, connected: int}
     */
    public function getStatistics(int $timePeriod = 1200): array
    {
        $userResult = $this->connection->fetchOne(
            'SELECT COUNT(`id`) FROM `user`'
        );

        $time = time();

        $sessionResult = $this->connection->fetchOne(
            <<<SQL
                SELECT
                COUNT(DISTINCT `session`.`username`)
                FROM `session`
                INNER JOIN `user`
                ON `session`.`username` = `user`.`username`
                WHERE `session`.`expire` > ? AND `user`.`last_seen` > ?;
            SQL,
            [$time, $time - $timePeriod]
        );

        return [
            'users' => (int) $userResult,
            'connected' => (int) $sessionResult,
        ];
    }

    /**
     * Reads the free-form counters kept against a user, optionally narrowed to one key
     *
     * @return array<string, string>
     */
    public function getUserData(int $userId, ?string $key): array
    {
        $sql    = 'SELECT `key`, `value` FROM `user_data` WHERE `user` = ?';
        $params = [$userId];
        if ($key !== null) {
            $sql .= ' AND `key` = ?';
            $params[] = $key;
        }

        $result = $this->connection->query($sql, $params);

        $data = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[(string) $row['key']] = (string) $row['value'];
        }

        return $data;
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

        $users = [];
        $sql   = ($includeDisabled)
            ? 'SELECT `id` FROM `user`;'
            : "SELECT `id` FROM `user` WHERE `disabled` = '0';";

        $dbResults = $this->connection->query($sql);
        while ($userId = $dbResults->fetchColumn()) {
            $users[] = (int) $userId;
        }

        User::add_to_cache($key, $value, $users);

        return $users;
    }

    /**
     * This returns all valid users in an array (id => name).
     *
     * @return string[]
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

        $users = [];
        $sql   = ($includeDisabled)
            ? 'SELECT `id`, `username` FROM `user`;'
            : "SELECT `id`, `username` FROM `user` WHERE `disabled` = '0';";

        $dbResults = $this->connection->query($sql);
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $users[(int) $row['id']] = $row['username'];
        }

        User::add_to_cache($key, $value, $users);

        return $users;
    }

    /**
     * Retrieve the validation code of a certain user by its username
     */
    public function getValidationByUsername(string $username): ?string
    {
        $validation = $this->connection->fetchOne(
            'SELECT `validation` FROM `user` WHERE `username` = ?',
            [$username]
        );

        return ($validation === false || $validation === null)
            ? null
            : (string) $validation;
    }

    /**
     * Whether another admin account exists, so the caller can refuse to strip the last one
     */
    public function hasOtherAdmin(int $excludingUserId, bool $enabledOnly): bool
    {
        $sql = ($enabledOnly)
            ? "SELECT `id` FROM `user` WHERE `disabled` = '0' AND `access` = ? AND `id` != ? "
            : 'SELECT `id` FROM `user` WHERE `access`= ? AND `id` != ?';

        return $this->connection->fetchOne($sql, [AccessLevelEnum::ADMIN->value, $excludingUserId]) !== false;
    }

    /**
     * Lookup for a user id with a certain email
     */
    public function idByEmail(string $email): int
    {
        $userId = $this->connection->fetchOne(
            'SELECT `id` FROM `user` WHERE `email` = ?',
            [$email]
        );

        return ($userId === false)
            ? 0
            : (int) $userId;
    }

    /**
     * Look up a user id by reset token (DOES NOT FIND ADMIN USERS)
     */
    public function idByResetToken(string $token): int
    {
        $dbResults = $this->connection->query('SELECT `id`, `username`, `email` FROM `user` WHERE `access` != 100;');
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $email_hash = hash('sha256', (string) $row['email']);
            $user_token = hash('sha256', $row['username'] . $email_hash);
            if ($token === $user_token) {
                return (int) $row['id'];
            }
        }

        return 0;
    }

    /**
     * Lookup for a user id with a certain name
     */
    public function idByUsername(string $username): int
    {
        if ($username === '-1') {
            return 0;
        }

        $userId = $this->connection->fetchOne(
            'SELECT `id` FROM `user` WHERE `username` = ?',
            [$username]
        );

        return ($userId === false)
            ? 0
            : (int) $userId;
    }

    /**
     * Puts the users of one catalog filter group back on DEFAULT, after that group is deleted
     */
    public function resetCatalogFilterGroup(int $groupId): void
    {
        $this->connection->query(
            'UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` = ?',
            [$groupId]
        );
    }

    /**
     * Puts every user pointing at a catalog filter group that no longer exists back on DEFAULT
     */
    public function resetMissingCatalogFilterGroups(): void
    {
        $this->connection->query(
            'UPDATE `user` SET `catalog_filter_group` = 0 WHERE `catalog_filter_group` NOT IN (SELECT `id` FROM `catalog_filter_group`);'
        );
    }

    /**
     * Get the current hashed user password
     */
    public function retrievePasswordFromUser(int $userId): string
    {
        $password = $this->connection->fetchOne(
            'SELECT `password` FROM `user` WHERE `id` = ?',
            [$userId]
        );

        return ($password === false || $password === null)
            ? ''
            : (string) $password;
    }

    /**
     * Writes a single user column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $userId, UserFieldEnum $field, int|string|null $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `user` SET `%s` = ? WHERE `id` = ?', $field->value),
                [$value, $userId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Writes a free-form counter against a user, replacing whatever was there
     */
    public function setUserData(int $userId, string $key, float|int|string $value): void
    {
        $this->connection->query(
            'REPLACE INTO `user_data` SET `user` = ?, `key` = ?, `value` = ?;',
            [$userId, $key, $value]
        );
    }

    /**
     * Writes a fresh validation key and disables the account until it is used
     */
    public function setValidation(int $userId, string $validation): bool
    {
        try {
            $this->connection->query(
                "UPDATE `user` SET `validation` = ?, `disabled`='1' WHERE `id` = ?",
                [$validation, $userId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Updates a users api key
     */
    public function updateApiKey(int $userId, string $apikey): void
    {
        $this->connection->query(
            'UPDATE `user` SET `apikey` = ? WHERE `id` = ?',
            [$apikey, $userId]
        );
    }

    /**
     * updates the last seen data for the user
     */
    public function updateLastSeen(
        int $userId,
    ): void {
        $this->connection->query(
            'UPDATE `user` SET `last_seen` = ? WHERE `id` = ?',
            [time(), $userId]
        );
    }

    /**
     * Updates a users RSS token
     */
    public function updateRssToken(int $userId, string $rssToken): void
    {
        $this->connection->query(
            'UPDATE `user` SET `rsstoken` = ? WHERE `id` = ?',
            [$rssToken, $userId]
        );
    }

    /**
     * Updates a users Stream token
     */
    public function updateStreamToken(int $userId, string $userName, string $streamToken): void
    {
        $this->connection->query(
            'UPDATE `user` SET `streamtoken` = ? WHERE `id` = ?',
            [$streamToken, $userId]
        );
    }

    /**
     * Stores the encrypted Subsonic secret for a user, or clears it when `null` is given
     */
    public function updateSubsonicSecret(int $userId, ?string $secret): void
    {
        $this->connection->query(
            'UPDATE `user` SET `subsonic_secret` = ? WHERE `id` = ?',
            [$secret, $userId]
        );
    }
}
