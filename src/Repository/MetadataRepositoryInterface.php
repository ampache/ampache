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

namespace Ampache\Repository;

use Ampache\Repository\Model\MetadataFieldInterface;
use Ampache\Repository\Model\MetadataInterface;
use Generator;

interface MetadataRepositoryInterface
{
    public function collectGarbage(): void;

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void;

    /**
     * @return Generator<array{
     *  id: int,
     *  name: string,
     *  public: int
     * }>
     */
    public function findAllFields(): Generator;

    /**
     * @return Generator<MetadataInterface>
     */
    public function findMetadataByObjectIdAndType(int $objectId, string $type): Generator;

    public function findMetadataByObjectIdAndFieldAndType(
        int $objectId,
        MetadataFieldInterface $field,
        string $type
    ): ?MetadataInterface;

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
    ): array;


    /**
     * @return array{
     *  id?: int,
     *  name?: string,
     *  public?: int
     * }
     */
    public function getFieldDbData(
        int $metadataFieldId
    ): array;

    public function addMetadata(
        MetadataFieldInterface $metadataField,
        int $objectId,
        string $type,
        string $data
    ): void;

    public function addMetadataField(
        string $name,
        int $public
    ): MetadataFieldInterface;

    public function findFieldByName(string $name): ?MetadataFieldInterface;

    public function updateMetadata(MetadataInterface $metadata, string $data): void;
}
