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

use Ampache\Module\System\Dba;
use Ampache\Module\System\LegacyLogger;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class ShoutRepository implements ShoutRepositoryInterface
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    /**
     * @return int[]
     */
    public function getBy(string $objectType, int $objectId): array
    {
        $dbResults = $this->connection->executeQuery(
            'SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
            [$objectType, $objectId]
        );

        $results = [];

        while ($rowId = $dbResults->fetchOne()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Cleans out orphaned shoutbox items
     */
    public function collectGarbage(
        ?string $object_type = null,
        ?int $object_id = null
    ): void {
        $types = ['song', 'album', 'artist', 'label'];

        if ($object_type !== null) {
            if (in_array($object_type, $types)) {
                $this->connection->executeQuery(
                    'DELETE FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ?',
                    [$object_type, $object_id]
                );
            } else {
                $this->logger->critical(
                    'Garbage collect on type `' . $object_type . '` is not supported.',
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
        } else {
            foreach ($types as $type) {
                $this->connection->executeQuery(
                    'DELETE FROM `user_shout` USING `user_shout` LEFT JOIN `' . $type . '` ON `' . $type . '`.`id` = `user_shout`.`object_id` WHERE `' . $type . '`.`id` IS NULL AND `user_shout`.`object_type` = ?',
                    [$type]
                );
            }
        }
    }

    /**
     * this function deletes the shoutbox entry
     */
    public function delete(int $shoutId): void
    {
        $this->connection->executeQuery(
            'DELETE FROM `user_shout` WHERE `id` = ?',
            [$shoutId]
        );
    }

    /**
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     *
     * @return int[]
     */
    public function getTop(int $limit, ?int $userId = null): array
    {
        $sql        = 'SELECT `id` FROM `user_shout` WHERE `sticky`=\'1\' ORDER BY `date` DESC';
        $db_results = Dba::read($sql);

        $shouts = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = (int) $row['id'];
        }

        // If we've already got too many stop here
        if (count($shouts) > $limit) {
            $shouts = array_slice($shouts, 0, $limit);

            return $shouts;
        }

        // Only get as many as we need
        $limit  = (int)($limit) - count($shouts);
        $params = [];
        $sql    = 'SELECT `id` FROM `user_shout` WHERE `sticky`=\'0\' ';
        if ($userId !== null) {
            $sql .= 'AND `user` = ? ';
            $params[] = $userId;
        }
        $sql .= sprintf('ORDER BY `date` DESC LIMIT %d', $limit);
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = (int) $row['id'];
        }

        return $shouts;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->executeQuery(
            'UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * Inserts a new shout item and returns the created id
     */
    public function insert(
        int $userId,
        int $date,
        string $comment,
        int $sticky,
        int $objectId,
        string $objectType,
        string $data
    ): int {
        $this->connection->executeQuery(
            'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (? , ?, ?, ?, ?, ?, ?)',
            [$userId, $date, $comment, $sticky, $objectId, $objectType, $data]
        );

        return (int) $this->connection->lastInsertId();
    }

    /**
     * This updates a shoutbox entry
     */
    public function update(int $shoutId, string $comment, bool $isSticky): void
    {
        $this->connection->executeQuery(
            'UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?',
            [$comment, (int) $isSticky, $shoutId]
        );
    }

    /**
     * @return array{
     *  id: int,
     *  user: int,
     *  text: string,
     *  date: int,
     *  sticky:int,
     *  object_id: int,
     *  object_type: string,
     *  data: string
     * }
     */
    public function getDataById(
        int $shoutId
    ): array {
        $db_results = Dba::read(
            'SELECT * FROM `user_shout` WHERE `id` = ?',
            [$shoutId]
        );

        return Dba::fetch_assoc($db_results);
    }
}
