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

use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CatalogCounterInterface;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;

/**
 * Manages database access related to Live-Streams (Radiostations)
 *
 * Tables: `live_stream`
 */
final readonly class LiveStreamRepository implements LiveStreamRepositoryInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection,
        private CatalogCounterInterface $catalogCounter,
    ) {}

    /**
     * This deletes the object with the given id from the database
     */
    public function delete(Live_Stream $liveStream): void
    {
        $this->connection->query(
            'DELETE FROM `live_stream` WHERE `id` = ?',
            [$liveStream->getId()]
        );

        $this->catalogCounter->count(CountableTableEnum::LIVE_STREAM);
    }

    /**
     * Removes every live streams of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool
    {
        try {
            $this->connection->query('DELETE FROM `live_stream` WHERE `catalog` = ?', [$catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Returns all items
     *
     * If a user is provided, the result will be limited to catalogs the user has access to
     *
     * @return int[]
     */
    public function findAll(
        ?User $user = null,
    ): array {
        $userId = $user?->getId();

        $db_results = $this->connection->query(
            'SELECT DISTINCT `live_stream`.`id` FROM `live_stream` WHERE `live_stream`.`catalog` IN (' . implode(',', Catalog::get_catalogs('', $userId, true)) . ');'
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
     * Saves the item, inserting it when it is new
     *
     * Returns the id of a newly created item, null when an existing one was updated
     */
    public function persist(Live_Stream $liveStream): ?int
    {
        if (!$liveStream->isNew()) {
            $this->connection->query(
                'UPDATE `live_stream` SET `name` = ?, `site_url` = ?, `url` = ?, `codec` = ? WHERE `id` = ?',
                [
                    $liveStream->name,
                    $liveStream->site_url,
                    $liveStream->url,
                    $liveStream->codec,
                    $liveStream->getId(),
                ]
            );

            return null;
        }

        $this->connection->query(
            'INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) VALUES (?, ?, ?, ?, ?)',
            [
                $liveStream->name,
                $liveStream->site_url,
                $liveStream->url,
                $liveStream->catalog,
                $liveStream->codec,
            ]
        );

        $insertedId = $this->connection->getLastInsertedId() ?: null;

        // the count is maintained here for both writes, so the model's create() no longer repeats it
        $this->catalogCounter->count(CountableTableEnum::LIVE_STREAM);

        return $insertedId;
    }
}
