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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Generator;
use PDO;

/**
 * Manages database access related to images (`Art`)
 *
 * Tables: `image`
 */
final readonly class ImageRepository implements ImageRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection) {}

    /**
     * Counts the stored images of one object at a size, which is how "does it have art" is answered
     */
    public function countByObject(string $objectType, int $objectId, string $size, string $kind): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(`id`) AS `nb_img` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ? AND `kind` = ?',
            [$objectType, $objectId, $size, $kind]
        );
    }

    /**
     * Drops one stored size of an object's art
     */
    public function deleteBySize(int $objectId, string $objectType, string $size, string $kind): void
    {
        $this->connection->query(
            'DELETE FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `kind` = ?',
            [$objectId, $objectType, $size, $kind]
        );
    }

    /**
     * Clear the image column (if you have the image on disk)
     */
    public function deleteImage(int $imageId): void
    {
        $this->connection->query(
            'UPDATE `image` SET `image` = NULL WHERE `id` = ?',
            [$imageId]
        );
    }

    /**
     * Copies every stored image of one object onto another, keeping whatever the target already had
     */
    public function duplicate(string $objectType, int $oldObjectId, string $writeType, int $newObjectId): void
    {
        $this->connection->query(
            'INSERT IGNORE INTO `image` (`image`, `width`, `height`, `mime`, `size`, `object_type`, `object_id`, `kind`) SELECT `image`, `width`, `height`, `mime`, `size`, ? AS `object_type`, ? AS `object_id`, `kind` FROM `image` WHERE `object_type` = ? AND `object_id` = ?',
            [$writeType, $newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * Get the object details for the art table
     *
     * @return Generator<array{id: int, object_id: int, object_type: string, size: string, mime: string}>
     */
    public function findAllImage(): Generator
    {
        $result = $this->connection->query(
            'SELECT `id`, `object_id`, `object_type`, `size`, `mime` FROM `image` WHERE `image` IS NOT NULL',
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'id' => (int) $row['id'],
                'object_id' => (int) $row['object_id'],
                'object_type' => $row['object_type'],
                'size' => (string) $row['size'],
                'mime' => (string) $row['mime'],
            ];
        }
    }

    /**
     * Reads the identity of one stored image at a size, falling back to nothing when it has none
     *
     * @return array<string, mixed>
     */
    public function findByObjectAndSize(string $objectType, int $objectId, string $size): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `id`, `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ?;',
            [$objectType, $objectId, $size]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * The id of the row the last write produced, or 0 when there was none
     */
    public function findLastInsertedId(): int
    {
        try {
            return $this->connection->getLastInsertedId();
        } catch (InsertIdInvalidException) {
            return 0;
        }
    }

    /**
     * Reads the sizes stored for an object, so the files behind them can be copied
     *
     * @return list<array{size: string, kind: string, mime: string}>
     */
    public function findSizes(string $objectType, int $objectId): array
    {
        $result = $this->connection->query(
            'SELECT `size`, `kind`, `mime` FROM `image` WHERE `object_type` = ? AND `object_id` = ?',
            [$objectType, $objectId]
        );

        $sizes = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $sizes[] = [
                'size' => (string) $row['size'],
                'kind' => (string) $row['kind'],
                'mime' => (string) $row['mime'],
            ];
        }

        return $sizes;
    }

    /**
     * Reads a stored thumbnail, matching an exact size or an original of those dimensions
     *
     * @return array<string, mixed>
     */
    public function findThumbnail(string $objectType, int $objectId, string $size, string $kind, int $width, int $height): array
    {
        if ($width > 0 && $height > 0) {
            $sql    = "SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND (`size` = ? OR (`size` = 'original' AND `width` = ? AND `height` = ?)) AND `kind` = ?";
            $params = [$objectType, $objectId, $size, $width, $height, $kind];
        } else {
            $sql    = 'SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = ? AND `kind` = ?';
            $params = [$objectType, $objectId, $size, $kind];
        }

        $row = $this->connection->fetchRow($sql, $params);

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads the original stored image of an object
     *
     * @return array<string, mixed>
     */
    public function getOriginalRow(string $objectType, int $objectId, string $kind): array
    {
        $row = $this->connection->fetchRow(
            "SELECT `id`, `image`, `width`, `height`, `mime`, `size` FROM `image` WHERE `object_type` = ? AND `object_id` = ? AND `size` = 'original' AND `kind` = ?",
            [$objectType, $objectId, $kind]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Get the object details for the art table
     */
    public function getRawImage(
        int $objectId,
        string $objectType,
        string $size,
        string $mimeType,
    ): ?string {
        $result = $this->connection->fetchOne(
            'SELECT `image` FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `mime` = ?',
            [
                $objectId,
                $objectType,
                $size,
                $mimeType
            ]
        );

        if ($result === false) {
            return null;
        }

        return (string) $result;
    }

    /**
     * Reads one whole image row by its id
     *
     * @return array<string, mixed>
     */
    public function getRowById(int $imageId): array
    {
        $row = $this->connection->fetchRow('SELECT * FROM `image` WHERE `id` = ?;', [$imageId]);

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads one stored image at an exact size
     *
     * @return array<string, mixed>
     */
    public function getRowBySize(string $size, string $objectType, int $objectId, string $kind): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `image`, `mime` FROM `image` WHERE `size` = ? AND `object_type` = ? AND `object_id` = ? AND `kind` = ?',
            [$size, $objectType, $objectId, $kind]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads the identity of the images belonging to a set of objects, for the in-request cache
     *
     * @param list<int|string> $objectIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByObjectIds(array $objectIds, ?string $objectType = null): array
    {
        if ($objectIds === []) {
            return [];
        }

        $sql = sprintf(
            'SELECT `object_type`, `object_id`, `mime`, `size` FROM `image` WHERE `object_id` IN (%s)',
            implode(',', array_map(intval(...), $objectIds))
        );

        $params = [];
        if ($objectType !== null) {
            $sql .= ' AND `object_type` = ?';
            $params[] = $objectType;
        }

        $result = $this->connection->query($sql, $params);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

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
    ): void {
        $this->connection->query(
            'REPLACE INTO `image` (`image`, `width`, `height`, `mime`, `size`, `object_type`, `object_id`, `kind`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
            [$image, $width, $height, $mime, $size, $objectType, $objectId, $kind]
        );
    }
}
