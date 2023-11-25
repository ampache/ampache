<?php

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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use DateTimeInterface;
use Generator;
use Psr\Log\LoggerInterface;

/**
 * Manages shout-box related database access
 *
 * Table: `user_shout`
 */
final class ShoutRepository implements ShoutRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        DatabaseConnectionInterface $connection,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory
    ) {
        $this->connection   = $connection;
        $this->logger       = $logger;
        $this->modelFactory = $modelFactory;
    }

    /**
     * Returns all shout-box items for the provided object-type and -id
     *
     * @return Generator<Shoutbox>
     */
    public function getBy(
        string $objectType,
        int $objectId
    ): Generator {
        $result = $this->connection->query(
            'SELECT `id` FROM `user_shout` WHERE `object_type` = ? AND `object_id` = ? ORDER BY `sticky`, `date` DESC',
            [$objectType, $objectId]
        );

        while ($row = $result->fetchColumn()) {
            yield $this->modelFactory->createShoutbox((int) $row);
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
     * this function deletes the shout-box entry
     */
    public function delete(int $shoutBoxId): void
    {
        $this->connection->query(
            'DELETE FROM `user_shout` WHERE `id` = ?',
            [$shoutBoxId]
        );
    }

    /**
     * Updates the ShoutBox item with the provided data
     *
     * @param array{comment: string, sticky: bool} $data
     */
    public function update(Shoutbox $shout, array $data): void
    {
        $this->connection->query(
            'UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?',
            [
                $data['comment'],
                (int) $data['sticky'],
                $shout->getId()
            ]
        );
    }

    /**
     * Creates a new shout entry and returns the id of the created shout item
     */
    public function create(
        User $user,
        DateTimeInterface $date,
        string $text,
        bool $isSticky,
        library_item $libItem,
        string $objectType,
        int $offset
    ): int {
        $this->connection->query(
            'INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $user->getId(),
                $date->getTimestamp(),
                $text,
                (int) $isSticky,
                $libItem->getId(),
                $objectType,
                $offset
            ]
        );

        return $this->connection->getLastInsertedId();
    }
}
