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
use Ampache\Repository\Model\MetadataField;
use Generator;
use PDO;

/**
 * Manages song metadata-fields related database access
 *
 * Tables: `metadata_field`
 */
final class MetadataFieldRepository implements MetadataFieldRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(
        DatabaseConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Remove metadata for songs which don't exist anymore
     */
    public function collectGarbage(): void
    {
        $this->connection->query('DELETE FROM `metadata_field` USING `metadata_field` LEFT JOIN `metadata` ON `metadata`.`field` = `metadata_field`.`id` WHERE `metadata`.`id` IS NULL;');
    }

    /**
     * Returns the list of available fields
     *
     * Key is the primary key, value the name
     *
     * @return Generator<int, string>
     */
    public function getPropertyList(): Generator
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `metadata_field`');

        while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            yield (int) $data['id'] => $data['name'];
        }
    }

    /**
     * Finds a single `metadata-field` item by its id
     */
    public function findById(int $fieldId): ?MetadataField
    {
        $result = $this->connection->query(
            'SELECT * FROM `metadata_field` WHERE `id` = ?',
            [
                $fieldId
            ],
        );

        $result->setFetchMode(PDO::FETCH_CLASS, MetadataField::class, [$this]);

        $metadataField = $result->fetch();

        if ($metadataField === false) {
            return null;
        }

        return $metadataField;
    }

    /**
     * Finds a single `metadata-field` item by its name
     */
    public function findByName(string $name): ?MetadataField
    {
        $result = $this->connection->query(
            'SELECT * FROM `metadata_field` WHERE `name` = ? LIMIT 1',
            [
                $name
            ],
        );

        $result->setFetchMode(PDO::FETCH_CLASS, MetadataField::class, [$this]);

        $metadataField = $result->fetch();

        if ($metadataField === false) {
            return null;
        }

        return $metadataField;
    }

    /**
     * Saves the item
     *
     * @return null|int The id of the item if the item was new
     */
    public function persist(MetadataField $field): ?int
    {
        $result = null;

        if ($field->isNew()) {
            $this->connection->query(
                'INSERT INTO `metadata_field` (`name`, `public`) VALUES (?, ?)',
                [
                    $field->getName(),
                    $field->isPublic()
                ]
            );

            $result = $this->connection->getLastInsertedId();
        } else {
            $this->connection->query(
                'UPDATE `metadata_field` SET `name` = ?, `public` = ? WHERE `id` = ?',
                [
                    $field->getName(),
                    $field->isPublic(),
                    $field->getId()
                ]
            );
        }

        return $result;
    }

    /**
     * Creates a new `metadata-field` item
     */
    public function prototype(): MetadataField
    {
        return new MetadataField($this);
    }
}
