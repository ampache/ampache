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

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\MetadataFieldInterface;
use Ampache\Repository\Model\MetadataInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class MetadataRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $modelFactory;

    private MetadataRepository $subject;

    public function setUp(): void
    {
        $this->database     = $this->mock(Connection::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new MetadataRepository(
            $this->database,
            $this->modelFactory
        );
    }

    public function testCollectGarbageCollects(): void
    {
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL'
            )
            ->once();
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `metadata_field` USING `metadata_field` LEFT JOIN `metadata` ON `metadata`.`field` = `metadata_field`.`id` WHERE `metadata`.`id` IS NULL'
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testMigrateMigrates(): void
    {
        $objectType = 'some-object-type';
        $oldId      = 66;
        $newId      = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?',
                [$newId, $oldId, ucfirst($objectType)]
            )
            ->once();

        $this->subject->migrate($objectType, $oldId, $newId);
    }

    public function testFindAllFieldsReturnsGenerator(): void
    {
        $data = ['some-result'];

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with('SELECT id, name, public FROM `metadata_field`')
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchAssociative')
            ->withNoArgs()
            ->twice()
            ->andReturn($data, false);

        $this->assertSame(
            [$data],
            iterator_to_array($this->subject->findAllFields())
        );
    }

    public function testFindMetadataByObjectIdAndTypeReturnsGenerator(): void
    {
        $objectId   = 666;
        $type       = 'some-type';
        $metadataId = 42;

        $result   = $this->mock(Result::class);
        $metadata = $this->mock(MetadataInterface::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT id FROM `metadata` WHERE object_id = ? AND type = ?',
                [$objectId, $type]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $metadataId, false);

        $this->modelFactory->shouldReceive('createMetadata')
            ->with($metadataId)
            ->once()
            ->andReturn($metadata);

        $this->assertSame(
            [$metadata],
            iterator_to_array($this->subject->findMetadataByObjectIdAndType($objectId, $type))
        );
    }

    public function testFindMetadataByObjectIdAndFieldAndTypeReturnsData(): void
    {
        $objectId   = 666;
        $type       = 'some-type';
        $metadataId = 42;
        $fieldId    = 33;

        $metadata = $this->mock(MetadataInterface::class);
        $field    = $this->mock(MetadataFieldInterface::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT id FROM `metadata` WHERE object_id = ? AND field = ? AND type = ? LIMIT 1',
                [$objectId, $fieldId, $type]
            )
            ->once()
            ->andReturn((string) $metadataId);

        $field->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($fieldId);

        $this->modelFactory->shouldReceive('createMetadata')
            ->with($metadataId)
            ->once()
            ->andReturn($metadata);

        $this->assertSame(
            $metadata,
            $this->subject->findMetadataByObjectIdAndFieldAndType($objectId, $field, $type)
        );
    }

    public function testFindMetadataByObjectIdAndFieldAndTypeReturnsNullIfNothingWasFound(): void
    {
        $objectId = 666;
        $type     = 'some-type';
        $fieldId  = 33;

        $field = $this->mock(MetadataFieldInterface::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT id FROM `metadata` WHERE object_id = ? AND field = ? AND type = ? LIMIT 1',
                [$objectId, $fieldId, $type]
            )
            ->once()
            ->andReturnFalse();

        $field->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($fieldId);

        $this->assertNull(
            $this->subject->findMetadataByObjectIdAndFieldAndType($objectId, $field, $type)
        );
    }

    public function testGetDbDataReturnsEmptyArrayIfNothingWasFound(): void
    {
        $metadataId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `metadata` WHERE id = ?',
                [$metadataId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDbData($metadataId)
        );
    }

    public function testGetDbDataReturnsResult(): void
    {
        $metadataId = 666;
        $result     = ['some-result'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `metadata` WHERE id = ?',
                [$metadataId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDbData($metadataId)
        );
    }

    public function testGetFieldDbDataReturnsEmptyArrayIfNothingWasFound(): void
    {
        $metadataFieldId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `metadata_field` WHERE id = ?',
                [$metadataFieldId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getFieldDbData($metadataFieldId)
        );
    }

    public function testGetFieldDbDataReturnsResult(): void
    {
        $metadataFieldId = 666;
        $result          = ['some-result'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `metadata_field` WHERE id = ?',
                [$metadataFieldId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getFieldDbData($metadataFieldId)
        );
    }

    public function testAddMetadataAdds(): void
    {
        $field = $this->mock(MetadataFieldInterface::class);

        $objectId = 666;
        $type     = 'some-string';
        $data     = 'some-data';
        $fieldId  = 42;

        $field->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($fieldId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `metadata` (object_id, field, data, type) VALUES (?, ?, ?, ?)',
                [$objectId, $fieldId, $data, $type]
            )
            ->once();

        $this->subject->addMetadata($field, $objectId, $type, $data);
    }

    public function testAddMetadataFieldAdds(): void
    {
        $name    = 'some-name';
        $public  = 1;
        $fieldId = 666;

        $field = $this->mock(MetadataFieldInterface::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `metadata_field` (name, public) VALUES (?, ?)',
                [$name, $public]
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $fieldId);

        $this->modelFactory->shouldReceive('createMetadataField')
            ->with($fieldId)
            ->once()
            ->andReturn($field);

        $this->assertSame(
            $field,
            $this->subject->addMetadataField($name, $public)
        );
    }

    public function testFindFieldByNameReturnsNullIfNothingWasFound(): void
    {
        $name = 'some-name';

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT id FROM `metadata_field` WHERE name = ? LIMIT 1',
                [$name]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findFieldByName($name)
        );
    }

    public function testFindFieldByNameReturnsField(): void
    {
        $name    = 'some-name';
        $fieldId = 666;

        $field = $this->mock(MetadataFieldInterface::class);

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT id FROM `metadata_field` WHERE name = ? LIMIT 1',
                [$name]
            )
            ->once()
            ->andReturn((string) $fieldId);

        $this->modelFactory->shouldReceive('createMetadataField')
            ->with($fieldId)
            ->once()
            ->andReturn($field);

        $this->assertSame(
            $field,
            $this->subject->findFieldByName($name)
        );
    }

    public function testUpdateMetadataUpdates(): void
    {
        $metadataId = 666;
        $data       = 'some-data';

        $metadata = $this->mock(MetadataInterface::class);

        $metadata->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($metadataId);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `metadata` SET data = ? WHERE id = ?',
                [$data, $metadataId]
            )
            ->once();

        $this->subject->updateMetadata($metadata, $data);
    }
}
