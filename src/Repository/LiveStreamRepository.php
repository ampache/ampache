<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;

/**
 * Manages database access related to Live-Streams (Radiostations)
 *
 * Tables: `live_stream`
 */
final class LiveStreamRepository implements LiveStreamRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private DatabaseConnectionInterface $connection;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        DatabaseConnectionInterface $connection
    ) {
        $this->modelFactory = $modelFactory;
        $this->connection   = $connection;
    }

    /**
     * Returns all items
     *
     * If a user is provided, the result will be limited to catalogs the user has access to
     *
     * @return list<int>
     */
    public function findAll(
        ?User $user = null
    ): array {
        $userId = null;

        if ($user !== null) {
            $userId = $user->getId();
        }

        $db_results = $this->connection->query(
            'SELECT DISTINCT `live_stream`.`id` FROM `live_stream` INNER JOIN `catalog_map` ON `catalog_map`.`object_id` = `live_stream`.`id` AND `catalog_map`.`object_type` = \'live_stream\' AND `catalog_map`.`catalog_id` IN (' . implode(',', Catalog::get_catalogs('', $userId, true)) . ');'
        );

        $result = [];
        while ($rowId = $db_results->fetchColumn()) {
            $result[] = (int) $rowId;
        }

        return $result;
    }

    /**
     * Finds a single item by its id
     */
    public function findById(int $objectId): ?Live_Stream
    {
        $result = $this->modelFactory->createLiveStream($objectId);

        if ($result->isNew()) {
            return null;
        }

        return $result;
    }

    /**
     * This deletes the object with the given id from the database
     */
    public function delete(Live_Stream $liveStream): void
    {
        $this->connection->query(
            'DELETE FROM `live_stream` WHERE `id` = ?',
            [$liveStream->getId()]
        );

        Catalog::count_table('live_stream');
    }
}
