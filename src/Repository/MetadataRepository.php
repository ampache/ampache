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
 *
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Repository\Model\MetadataFieldInterface;
use Ampache\Repository\Model\MetadataInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;
use Generator;

final class MetadataRepository implements MetadataRepositoryInterface
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

    public function collectGarbage(): void
    {
        $this->database->executeQuery(
            'DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL'
        );

        $this->database->executeQuery(
            'DELETE FROM `metadata_field` USING `metadata_field` LEFT JOIN `metadata` ON `metadata`.`field` = `metadata_field`.`id` WHERE `metadata`.`id` IS NULL'
        );
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->database->executeQuery(
            'UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?',
            [$newObjectId, $oldObjectId, ucfirst($objectType)]
        );
    }

    /**
     * @return Generator<array{
     *  id: int,
     *  name: string,
     *  public: int
     * }>
     */
    public function findAllFields(): Generator
    {
        $result = $this->database->executeQuery('SELECT id, name, public FROM `metadata_field`');

        while ($row = $result->fetchAssociative()) {
            yield $row;
        }
    }

    /**
     * @return Generator<MetadataInterface>
     */
    public function findMetadataByObjectIdAndType(int $objectId, string $type): Generator
    {
        $result = $this->database->executeQuery(
            'SELECT id FROM `metadata` WHERE object_id = ? AND type = ?',
            [$objectId, $type]
        );

        while ($rowId = $result->fetchOne()) {
            yield $this->modelFactory->createMetadata((int) $rowId);
        }
    }

    public function findMetadataByObjectIdAndFieldAndType(
        int $objectId,
        MetadataFieldInterface $field,
        string $type
    ): ?MetadataInterface {
        $result = $this->database->fetchOne(
            'SELECT id FROM `metadata` WHERE object_id = ? AND field = ? AND type = ? LIMIT 1',
            [$objectId, $field->getId(), $type]
        );

        if ($result === false) {
            return null;
        }

        return $this->modelFactory->createMetadata((int) $result);
    }

    /**
     * @return array{
     *  id?: int,
     *  object_id?: int,
     *  field?: int,
     *  data?: string,
     *  type?: string
     * }
     */
    public function getDbData(
        int $metadataId
    ): array {
        $result = $this->database->fetchAssociative(
            'SELECT * FROM `metadata` WHERE id = ?',
            [$metadataId]
        );

        if ($result === false) {
            return [];
        }

        return $result;
    }

    /**
     * @return array{
     *  id?: int,
     *  name?: string,
     *  public?: int
     * }
     */
    public function getFieldDbData(
        int $metadataFieldId
    ): array {
        $result = $this->database->fetchAssociative(
            'SELECT * FROM `metadata_field` WHERE id = ?',
            [$metadataFieldId]
        );

        if ($result === false) {
            return [];
        }

        return $result;
    }

    public function addMetadata(
        MetadataFieldInterface $metadataField,
        int $objectId,
        string $type,
        string $data
    ): void {
        $this->database->executeQuery(
            'INSERT INTO `metadata` (object_id, field, data, type) VALUES (?, ?, ?, ?)',
            [$objectId, $metadataField->getId(), $data, $type]
        );
    }

    public function addMetadataField(
        string $name,
        int $public
    ): MetadataFieldInterface {
        $this->database->executeQuery(
            'INSERT INTO `metadata_field` (name, public) VALUES (?, ?)',
            [$name, $public]
        );

        return $this->modelFactory->createMetadataField((int) $this->database->lastInsertId());
    }

    public function findFieldByName(string $name): ?MetadataFieldInterface
    {
        $result = $this->database->fetchOne(
            'SELECT id FROM `metadata_field` WHERE name = ? LIMIT 1',
            [$name]
        );

        if ($result === false) {
            return null;
        }

        return $this->modelFactory->createMetadataField((int) $result);
    }

    public function updateMetadata(MetadataInterface $metadata, string $data): void
    {
        $this->database->executeQuery(
            'UPDATE `metadata` SET data = ? WHERE id = ?',
            [$data, $metadata->getId()]
        );
    }
}
