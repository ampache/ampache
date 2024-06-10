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
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\MetadataField;
use Generator;
use PDO;

/**
 * Manages song metadata related database access
 *
 * Tables: `metadata`
 */
final readonly class MetadataRepository implements MetadataRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private MetadataFieldRepositoryInterface $metadataFieldRepository
    ) {
    }

    /**
     * Remove metadata for songs which don't exist anymore
     */
    public function collectGarbage(): void
    {
        $this->connection->query('DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL;');
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?',
            [
                $newObjectId,
                $oldObjectId,
                ucfirst($objectType),
            ],
        );
    }

    /**
     * Finds a single `metadata` item by its id
     */
    public function findById(int $metadataId): ?Metadata
    {
        $result = $this->connection->query(
            'SELECT * FROM `metadata` WHERE `id` = ?',
            [
                $metadataId
            ],
        );

        $result->setFetchMode(PDO::FETCH_CLASS, Metadata::class, [$this, $this->metadataFieldRepository]);

        $metadata = $result->fetch();

        if ($metadata === false) {
            return null;
        }

        return $metadata;
    }

    /**
     * Finds a single `metadata`-item by its object-id, field and type
     */
    public function findByObjectIdAndFieldAndType(
        int $objectId,
        MetadataField $field,
        string $objectType
    ): ?Metadata {
        $result = $this->connection->query(
            'SELECT * FROM `metadata` WHERE `object_id` = ? AND `type` = ? AND `field` = ? LIMIT 1',
            [
                $objectId,
                ucfirst($objectType),
                $field->getId()
            ],
        );

        $result->setFetchMode(PDO::FETCH_CLASS, Metadata::class, [$this, $this->metadataFieldRepository]);

        $metadata = $result->fetch();

        if ($metadata === false) {
            return null;
        }

        return $metadata;
    }

    /**
     * Returns all `metadata`-items for a certain object-type combo
     *
     * @return Generator<Metadata>
     */
    public function findByObjectIdAndType(
        int $objectId,
        string $objectType
    ): Generator {
        $result = $this->connection->query(
            'SELECT * FROM `metadata` WHERE `object_id` = ? AND `type` = ?',
            [
                $objectId,
                ucfirst($objectType),
            ],
        );

        $result->setFetchMode(PDO::FETCH_CLASS, Metadata::class, [$this, $this->metadataFieldRepository]);

        while ($metadata = $result->fetch()) {
            yield $metadata;
        }
    }

    /**
     * Deletes the `metadata` item
     */
    public function remove(Metadata $metadata): void
    {
        $this->connection->query(
            'DELETE FROM `metadata` where `id` = ?',
            [
                $metadata->getId()
            ]
        );
    }

    /**
     * Creates a new `metadata` item
     */
    public function prototype(): Metadata
    {
        return new Metadata(
            $this,
            $this->metadataFieldRepository
        );
    }

    /**
     * Saves the item
     *
     * @return null|int The id of the item if the item was new
     */
    public function persist(Metadata $metadata): ?int
    {
        $result = null;

        if ($metadata->isNew()) {
            $this->connection->query(
                'INSERT INTO `metadata` (`object_id`, `field`, `data`, `type`) VALUES (?, ?, ?, ?)',
                [
                    $metadata->getObjectId(),
                    $metadata->getFieldId(),
                    $metadata->getData(),
                    $metadata->getType()
                ]
            );

            $result = $this->connection->getLastInsertedId();
        } else {
            $this->connection->query(
                'UPDATE `metadata` SET `object_id` = ?, `field` = ?, `data` = ?, `type` = ? WHERE `id` = ?',
                [
                    $metadata->getObjectId(),
                    $metadata->getFieldId(),
                    $metadata->getData(),
                    $metadata->getType(),
                    $metadata->getId()
                ]
            );
        }

        return $result;
    }
}
