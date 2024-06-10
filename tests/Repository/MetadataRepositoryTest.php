<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\MetadataField;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private MetadataFieldRepositoryInterface&MockObject $metadataFieldRepository;

    private MetadataRepository $subject;

    protected function setUp(): void
    {
        $this->connection              = $this->createMock(DatabaseConnectionInterface::class);
        $this->metadataFieldRepository = $this->createMock(MetadataFieldRepositoryInterface::class);

        $this->subject = new MetadataRepository(
            $this->connection,
            $this->metadataFieldRepository
        );
    }

    public function testCollectGarbageExecutesQuery(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL;');

        $this->subject->collectGarbage();
    }

    public function testMigrateMigrates(): void
    {
        $objectType  = 'some-object-type';
        $oldObjectId = 666;
        $newObjectId = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?',
                [
                    $newObjectId,
                    $oldObjectId,
                    ucfirst($objectType),
                ],
            );

        $this->subject->migrate($objectType, $oldObjectId, $newObjectId);
    }

    public function testFindByIdReturnsNullIfNothingWasFound(): void
    {
        $metadataId = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata` WHERE `id` = ?',
                [
                    $metadataId
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Metadata::class, [$this->subject, $this->metadataFieldRepository]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findById($metadataId)
        );
    }

    public function testFindByIdReturnsFoundObject(): void
    {
        $metadataId = 666;

        $result   = $this->createMock(PDOStatement::class);
        $metadata = $this->createMock(Metadata::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata` WHERE `id` = ?',
                [
                    $metadataId
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Metadata::class, [$this->subject, $this->metadataFieldRepository]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn($metadata);

        static::assertSame(
            $metadata,
            $this->subject->findById($metadataId)
        );
    }

    public function testFindByObjectIdAndFieldAndTypeReturnsFoundObject(): void
    {
        $result        = $this->createMock(PDOStatement::class);
        $metadata      = $this->createMock(Metadata::class);
        $metadataField = $this->createMock(MetadataField::class);

        $objectType = 'some-object-type';
        $fieldId    = 666;
        $objectId   = 42;

        $metadataField->expects(static::once())
            ->method('getId')
            ->willReturn($fieldId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata` WHERE `object_id` = ? AND `type` = ? AND `field` = ? LIMIT 1',
                [
                    $objectId,
                    ucfirst($objectType),
                    $fieldId
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Metadata::class, [$this->subject, $this->metadataFieldRepository]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn($metadata);

        static::assertSame(
            $metadata,
            $this->subject->findByObjectIdAndFieldAndType($objectId, $metadataField, $objectType)
        );
    }

    public function testFindByObjectIdAndFieldAndTypeReturnsNullIfNothingWasFound(): void
    {
        $result        = $this->createMock(PDOStatement::class);
        $metadataField = $this->createMock(MetadataField::class);

        $objectType = 'some-object-type';
        $fieldId    = 666;
        $objectId   = 42;

        $metadataField->expects(static::once())
            ->method('getId')
            ->willReturn($fieldId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata` WHERE `object_id` = ? AND `type` = ? AND `field` = ? LIMIT 1',
                [
                    $objectId,
                    ucfirst($objectType),
                    $fieldId
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Metadata::class, [$this->subject, $this->metadataFieldRepository]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findByObjectIdAndFieldAndType($objectId, $metadataField, $objectType)
        );
    }

    public function testFindByObjectIdAndTypeReturnsFoundObject(): void
    {
        $result        = $this->createMock(PDOStatement::class);
        $metadata      = $this->createMock(Metadata::class);

        $objectType = 'some-object-type';
        $objectId   = 42;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata` WHERE `object_id` = ? AND `type` = ?',
                [
                    $objectId,
                    ucfirst($objectType),
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, Metadata::class, [$this->subject, $this->metadataFieldRepository]);
        $result->expects(static::exactly(2))
            ->method('fetch')
            ->willReturn($metadata, false);

        static::assertSame(
            [$metadata],
            iterator_to_array(
                $this->subject->findByObjectIdAndType($objectId, $objectType)
            )
        );
    }

    public function testRemoveDeletesItem(): void
    {
        $metadata = $this->createMock(Metadata::class);

        $metadataId = 666;

        $metadata->expects(static::once())
            ->method('getId')
            ->willReturn($metadataId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `metadata` where `id` = ?',
                [
                    $metadataId
                ]
            );

        $this->subject->remove($metadata);
    }

    public function testPrototypeReturnsObject(): void
    {
        static::assertInstanceOf(
            Metadata::class,
            $this->subject->prototype()
        );
    }

    public function testPersistWritesNewItemAndReturnsId(): void
    {
        $metadata = $this->createMock(Metadata::class);

        $metadataId = 666;
        $objectId   = 42;
        $fieldId    = 33;
        $data       = 'some-data';
        $type       = 'some-type';

        $metadata->expects(static::once())
            ->method('getObjectId')
            ->willReturn($objectId);
        $metadata->expects(static::once())
            ->method('getFieldId')
            ->willReturn($fieldId);
        $metadata->expects(static::once())
            ->method('getData')
            ->willReturn($data);
        $metadata->expects(static::once())
            ->method('getType')
            ->willReturn($type);
        $metadata->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `metadata` (`object_id`, `field`, `data`, `type`) VALUES (?, ?, ?, ?)',
                [
                    $objectId,
                    $fieldId,
                    $data,
                    $type
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($metadataId);

        static::assertSame(
            $metadataId,
            $this->subject->persist($metadata)
        );
    }

    public function testPersistUpdatesExistingItem(): void
    {
        $metadata = $this->createMock(Metadata::class);

        $metadataId = 666;
        $objectId   = 42;
        $fieldId    = 33;
        $data       = 'some-data';
        $type       = 'some-type';

        $metadata->expects(static::once())
            ->method('getObjectId')
            ->willReturn($objectId);
        $metadata->expects(static::once())
            ->method('getFieldId')
            ->willReturn($fieldId);
        $metadata->expects(static::once())
            ->method('getData')
            ->willReturn($data);
        $metadata->expects(static::once())
            ->method('getType')
            ->willReturn($type);
        $metadata->expects(static::once())
            ->method('isNew')
            ->willReturn(false);
        $metadata->expects(static::once())
            ->method('getId')
            ->willReturn($metadataId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `metadata` SET `object_id` = ?, `field` = ?, `data` = ?, `type` = ? WHERE `id` = ?',
                [
                    $objectId,
                    $fieldId,
                    $data,
                    $type,
                    $metadataId
                ]
            );

        static::assertNull(
            $this->subject->persist($metadata)
        );
    }
}
