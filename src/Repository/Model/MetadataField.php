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

use Ampache\Repository\MetadataRepositoryInterface;

final class MetadataField implements MetadataFieldInterface
{
    private MetadataRepositoryInterface $metadataRepository;

    private int $id;

    /**
     * @var null|array{
     *  id?: int,
     *  name?: string,
     *  public?: int
     * } $dbData
     */
    private ?array $dbData = null;

    public function __construct(
        MetadataRepositoryInterface $metadataRepository,
        int $id
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->id                 = $id;
    }

    /**
     * @return array{
     *  id?: int,
     *  name?: string,
     *  public?: int
     * }
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->metadataRepository->getFieldDbData($this->id);
        }

        return $this->dbData;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getId(): int
    {
        return (int) ($this->getDbData()['id'] ?? 0);
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    public function getPublic(): int
    {
        return (int) ($this->getDbData()['public'] ?? 0);
    }

    public function getFormattedName(): string
    {
        return ucwords(str_replace("_", " ", $this->getName()));
    }
}
