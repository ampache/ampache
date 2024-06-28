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

namespace Ampache\Repository\Model;

use Ampache\Repository\MetadataFieldRepositoryInterface;

/**
 * Represents metadata fields
 */
class MetadataField
{
    /**
     * Database ID
     */
    private int $id = 0;

    /**
     * Tag name
     */
    private string $name = '';

    /**
     * Is the Tag public?
     */
    private bool $public = true;

    private MetadataFieldRepositoryInterface $metadataFieldRepository;

    public function __construct(
        MetadataFieldRepositoryInterface $metadataFieldRepository
    ) {
        $this->metadataFieldRepository = $metadataFieldRepository;
    }

    /**
     * Returns `true` if the object is new
     */
    public function isNew(): bool
    {
        return $this->id === 0;
    }

    /**
     * Returns the items id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name
     */
    public function setName(string $name): MetadataField
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns `true` if the item is public
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * Sets the public-state of the item
     */
    public function setPublic(bool $public): MetadataField
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Saves the item
     */
    public function save(): void
    {
        $result = $this->metadataFieldRepository->persist($this);
        if ($result !== null) {
            $this->id = $result;
        }
    }
}
