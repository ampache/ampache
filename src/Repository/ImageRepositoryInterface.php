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

use Traversable;

interface ImageRepositoryInterface
{
    /**
     * Counts the stored images of one object at a size, which is how "does it have art" is answered
     */
    public function countByObject(string $objectType, int $objectId, string $size, string $kind): int;

    /**
     * Drops one stored size of an object's art
     */
    public function deleteBySize(int $objectId, string $objectType, string $size, string $kind): void;

    /**
     * Clear the image column (if you have the image on disk)
     */
    public function deleteImage(int $imageId): void;

    /**
     * Copies every stored image of one object onto another, keeping whatever the target already had
     */
    public function duplicate(string $objectType, int $oldObjectId, string $writeType, int $newObjectId): void;

    /**
     * Get the object details for the art table
     *
     * @return Traversable<array{id: int, object_id: int, object_type: string, size: string, mime: string}>
     */
    public function findAllImage(): Traversable;

    /**
     * Reads the identity of one stored image at a size, falling back to nothing when it has none
     *
     * @return array<string, mixed>
     */
    public function findByObjectAndSize(string $objectType, int $objectId, string $size): array;

    /**
     * The id of the row the last write produced, or 0 when there was none
     */
    public function findLastInsertedId(): int;

    /**
     * Reads the sizes stored for an object, so the files behind them can be copied
     *
     * @return list<array{size: string, kind: string, mime: string}>
     */
    public function findSizes(string $objectType, int $objectId): array;

    /**
     * Reads a stored thumbnail, matching an exact size or an original of those dimensions
     *
     * @return array<string, mixed>
     */
    public function findThumbnail(string $objectType, int $objectId, string $size, string $kind, int $width, int $height): array;

    /**
     * Reads the original stored image of an object
     *
     * @return array<string, mixed>
     */
    public function getOriginalRow(string $objectType, int $objectId, string $kind): array;

    /**
     * Get the object details for the art table
     */
    public function getRawImage(
        int $objectId,
        string $objectType,
        string $size,
        string $mimeType,
    ): ?string;

    /**
     * Reads one whole image row by its id
     *
     * @return array<string, mixed>
     */
    public function getRowById(int $imageId): array;

    /**
     * Reads one stored image at an exact size
     *
     * @return array<string, mixed>
     */
    public function getRowBySize(string $size, string $objectType, int $objectId, string $kind): array;

    /**
     * Reads the identity of the images belonging to a set of objects, for the in-request cache
     *
     * @param list<int|string> $objectIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByObjectIds(array $objectIds, ?string $objectType = null): array;

    /**
     * Stores an image, replacing whatever was held at that object/size/kind
     */
    public function replace(
        ?string $image,
        int $width,
        int $height,
        string $mime,
        string $size,
        string $objectType,
        int $objectId,
        string $kind,
    ): void;
}
