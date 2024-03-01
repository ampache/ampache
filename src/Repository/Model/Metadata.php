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

namespace Ampache\Repository\Model;

use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\MetadataRepositoryInterface;

/**
 * Represents metadata linked to library-items
 */
class Metadata
{
    /** @var int Database primary key */
    private int $id = 0;

    /** @var int Id of a library item like song or video */
    private int $object_id = 0;

    /** @var int Id of the linked MetadataField */
    private int $field = 0;

    /** @var string Tag Data */
    private string $data = '';

    /** @var string Object type (song, video, ...) */
    private string $type = '';

    private ?MetadataField $metadataField = null;

    private MetadataFieldRepositoryInterface $metadataFieldRepository;

    private MetadataRepositoryInterface $metadataRepository;

    public function __construct(
        MetadataRepositoryInterface $metadataRepository,
        MetadataFieldRepositoryInterface $metadataFieldRepository
    ) {
        $this->metadataRepository      = $metadataRepository;
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
     * Returns the object-id
     */
    public function getObjectId(): int
    {
        return $this->object_id;
    }

    /**
     * Returns the linked field (if available)
     */
    public function getField(): ?MetadataField
    {
        if ($this->metadataField === null) {
            $this->metadataField = $this->metadataFieldRepository->findById($this->field);
        }

        return $this->metadataField;
    }

    /**
     * Returns the data
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Sets the object-id
     */
    public function setObjectId(int $objectId): Metadata
    {
        $this->object_id = $objectId;

        return $this;
    }

    /**
     * Sets the metadata-field
     */
    public function setField(MetadataField $metadataField): Metadata
    {
        $this->metadataField = $metadataField;
        $this->field         = $metadataField->getId();

        return $this;
    }

    /**
     * Returns the id of the metadata-field
     */
    public function getFieldId(): int
    {
        return $this->field;
    }

    /**
     * Sets the id of the metadata-field
     */
    public function setFieldId(int $fieldId): Metadata
    {
        $this->field         = $fieldId;
        $this->metadataField = null;

        return $this;
    }

    /**
     * Sets the data
     */
    public function setData(string $data): Metadata
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns the object-type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the object-type
     */
    public function setType(string $type): Metadata
    {
        $this->type = ucfirst($type);

        return $this;
    }

    /**
     * Returns the metadata id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Saves the item
     */
    public function save(): void
    {
        $result = $this->metadataRepository->persist($this);

        if ($result !== null) {
            $this->id = $result;
        }
    }
}
