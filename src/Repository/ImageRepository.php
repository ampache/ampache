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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Generator;
use PDO;

/**
 * Manages database access related to images (`Art`)
 *
 * Tables: `image`
 */
final readonly class ImageRepository implements ImageRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
    }

    /**
     * Get the object details for the art table
     */
    public function getRawImage(
        int $objectId,
        string $objectType,
        string $size,
        string $mimeType
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
     * Clear the image column (if you have the image on disk)
     */
    public function deleteImage(int $imageId): void
    {
        $this->connection->query(
            'UPDATE `image` SET `image` = NULL WHERE `id` = ?',
            [$imageId]
        );
    }
}
