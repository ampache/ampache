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
 */

namespace Ampache\Module\Metadata;

use Ampache\Repository\Model\Metadata;
use Traversable;

interface MetadataManagerInterface
{
    /**
     * @return Traversable<Metadata>
     */
    public function getMetadata(MetadataEnabledInterface $item): Traversable;

    /**
     * Deletes a metadata-item
     */
    public function deleteMetadata(Metadata $metadata): void;

    /**
     * Return all disabled Metadata field names
     *
     * @return list<string>
     */
    public function getDisabledMetadataFields(): array;

    /**
     * Adds a new metadata item
     */
    public function addMetadata(MetadataEnabledInterface $item, string $name, string $data): void;

    public function updateOrAddMetadata(MetadataEnabledInterface $item, string $name, string $data): void;

    /**
     * Returns `true` if custom metadata is enabled
     */
    public function isCustomMetadataEnabled(): bool;

    /**
     * Cleans up metadata-related database tables
     */
    public function collectGarbage(): void;
}
