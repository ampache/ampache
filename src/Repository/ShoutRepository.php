<?php

declare(strict_types=1);

/**
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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Shoutbox;
use Generator;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Manages shout-box related database access
 *
 * Table: `user_shout`
 *
 * @extends BaseRepository<Shoutbox>
 */
final class ShoutRepository extends BaseRepository implements ShoutRepositoryInterface
{
    protected DatabaseConnectionInterface $connection;

    private LoggerInterface $logger;

    public function __construct(
        DatabaseConnectionInterface $connection,
        LoggerInterface $logger
    ) {
        $this->connection   = $connection;
        $this->logger       = $logger;
    }

    /**
     * @return class-string<Shoutbox>
     */
    protected function getModelClass(): string
    {
        return Shoutbox::class;
    }

    /**
     * @return list<mixed>
     */
    protected function getPrototypeParameters(): array
    {
        return [$this];
    }

    protected function getTableName(): string
    {
        return 'user_shout';
    }

    /**
     * Returns all shout-box items for the provided object-type and -id
     *
     * @return Generator<Shoutbox>
     */
    public function getBy(
        LibraryItemEnum $objectType,
        int $objectId
    ): Generator {
        $result = $this->connection->query(
            'SELECT * FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
            [$objectType->value, $objectId]
        );
        $result->setFetchMode(PDO::FETCH_CLASS, Shoutbox::class, [$this]);

        while ($shout = $result->fetch()) {
            yield $shout;
        }
    }

    /**
     * Cleans out orphaned shout-box items
     */
    public function collectGarbage(
        ?string $objectType = null,
        ?int $objectId = null
    ): void {
        $types = ['song', 'album', 'artist', 'label'];

        if ($objectType !== null) {
            // @todo use php8+ enum to get rid of this check
            if (in_array($objectType, $types)) {
                $this->connection->query(
                    'DELETE FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ?',
                    [$objectType, $objectId]
                );
            } else {
                $this->logger->critical(
                    sprintf('Garbage collect on type `%s` is not supported.', $objectType)
                );
            }
        } else {
            foreach ($types as $type) {
                $query = <<<SQL
                    DELETE FROM
                        `user_shout`
                    USING
                        `user_shout`
                    LEFT JOIN
                        `%1\$s`
                    ON
                        `%1\$s`.`id` = `user_shout`.`object_id`
                    WHERE
                        `%1\$s`.`id` IS NULL
                    AND
                        `user_shout`.`object_type` = ?
                SQL;

                $this->connection->query(
                    sprintf($query, $type),
                    [$type]
                );
            }
        }
    }

    /**
     * Persists the shout-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Shoutbox $shout): ?int
    {
        $result = null;

        if ($shout->isNew()) {
            $this->connection->query(
                'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $shout->getUserId(),
                    $shout->getDate()->getTimestamp(),
                    $shout->getText(),
                    (int) $shout->isSticky(),
                    $shout->getObjectId(),
                    $shout->getObjectType()->value,
                    $shout->getOffset()
                ]
            );

            $result = $this->connection->getLastInsertedId();
        } else {
            $this->connection->query(
                'UPDATE `user_shout` SET `user` = ?, `date` = ?, `text` = ?, `sticky` = ?, `object_id` = ?, `object_type` = ?, `data` = ? WHERE `id` = ?',
                [
                    $shout->getUserId(),
                    $shout->getDate()->getTimestamp(),
                    $shout->getText(),
                    (int) $shout->isSticky(),
                    $shout->getObjectId(),
                    $shout->getObjectType()->value,
                    $shout->getOffset(),
                    $shout->getId()
                ]
            );
        }

        return $result;
    }

    /**
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     *
     * @return Generator<Shoutbox>
     */
    public function getTop(int $limit, ?string $username = null): Generator
    {
        $result = $this->connection->query('SELECT * FROM `user_shout` WHERE `sticky` = 1 ORDER BY `date` DESC');

        $result->setFetchMode(PDO::FETCH_CLASS, Shoutbox::class, [$this]);

        while ($shout = $result->fetch()) {
            /** @var Shoutbox $shout */
            yield $shout;

            $limit--;

            if ($limit < 1) {
                break;
            }
        }

        $params  = [];
        $userSql = '';
        $sql     = <<<SQL
        SELECT
            `user_shout`.*
        FROM
            `user_shout`
        LEFT JOIN
            `user`
        ON
            `user`.`id` = `user_shout`.`user`
        WHERE
            `user_shout`.`sticky` = 0 %s
        ORDER BY
            `user_shout`.`date` DESC
        LIMIT %d
        SQL;

        if ($username !== null) {
            $userSql  = 'AND `user`.`username` = ?';
            $params[] = $username;
        }

        $result = $this->connection->query(
            sprintf(
                $sql,
                $userSql,
                $limit
            ),
            $params
        );

        $result->setFetchMode(PDO::FETCH_CLASS, Shoutbox::class, [$this]);

        while ($shout = $result->fetch()) {
            /** @var Shoutbox $shout */
            yield $shout;
        }
    }

    /**
     * Migrates an object associate shouts to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }
}
