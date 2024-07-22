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
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class ImageRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface $connection;

    private ImageRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new ImageRepository(
            $this->connection,
        );
    }

    public function testGetRawImageReturnsNullIfNotExisting(): void
    {
        $objectId   = 666;
        $objectType = 'some-type';
        $size       = 'some-size';
        $mimeType   = 'some-mimetype';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `image` FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `mime` = ?',
                [
                    $objectId,
                    $objectType,
                    $size,
                    $mimeType
                ]
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->getRawImage($objectId, $objectType, $size, $mimeType)
        );
    }

    public function testGetRawImageReturnsImage(): void
    {
        $objectId   = 666;
        $objectType = 'some-type';
        $size       = 'some-size';
        $mimeType   = 'some-mimetype';
        $result     = 'some-result';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `image` FROM `image` WHERE `object_id` = ? AND `object_type` = ? AND `size` = ? AND `mime` = ?',
                [
                    $objectId,
                    $objectType,
                    $size,
                    $mimeType
                ]
            )
            ->willReturn($result);

        static::assertSame(
            $result,
            $this->subject->getRawImage($objectId, $objectType, $size, $mimeType)
        );
    }

    public function testFindAllImage(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $rowId      = 666;
        $objectId   = 42;
        $objectType = 'some-type';
        $size       = 'some-size';
        $mimeType   = 'some-mimetype';

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `object_id`, `object_type`, `size`, `mime` FROM `image` WHERE `image` IS NOT NULL')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'id' => (string) $rowId,
                    'object_id' => (string) $objectId,
                    'object_type' => $objectType,
                    'size' => $size,
                    'mime' => $mimeType,
                ],
                false
            );

        static::assertSame(
            [[
                'id' => $rowId,
                'object_id' => $objectId,
                'object_type' => $objectType,
                'size' => $size,
                'mime' => $mimeType,
            ]],
            iterator_to_array($this->subject->findAllImage())
        );
    }

    public function testDeleteImageDeletes(): void
    {
        $imageId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `image` SET `image` = NULL WHERE `id` = ?',
                [
                    $imageId
                ]
            );

        $this->subject->deleteImage($imageId);
    }
}
