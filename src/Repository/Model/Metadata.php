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

namespace Ampache\Repository\Model;

use Ampache\Repository\MetadataRepository;

final class Metadata implements MetadataInterface
{
    private MetadataRepository $metadataRepository;

    private ModelFactoryInterface $modelFactory;

    private int $id;

    /**
     * @var null|array{
     *  id?: int,
     *  object_id?: int,
     *  field?: int,
     *  data?: string,
     *  type?: string
     * } $dbData
     */
    private ?array $dbData = null;

    public function __construct(
        MetadataRepository $metadataRepository,
        ModelFactoryInterface $modelFactory,
        int $id
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->modelFactory       = $modelFactory;
        $this->id                 = $id;
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
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->metadataRepository->getDbData($this->id);
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return (int) ($this->getDbData()['id'] ?? 0);
    }

    public function getObjectId(): int
    {
        return (int) ($this->getDbData()['object_id'] ?? 0);
    }

    public function getFieldId(): int
    {
        return (int) ($this->getDbData()['field'] ?? 0);
    }

    public function getData(): string
    {
        return $this->getDbData()['data'] ?? '';
    }

    public function getType(): string
    {
        return $this->getDbData()['type'] ?? '';
    }

    public function getField(): MetadataFieldInterface
    {
        return $this->modelFactory->createMetadataField($this->getFieldId());
    }
}
