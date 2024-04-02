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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\MetadataField;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetadataFieldRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private MetadataFieldRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new MetadataFieldRepository(
            $this->connection
        );
    }

    public function testCollectGarbageExecutesQuery(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with('DELETE FROM `metadata_field` USING `metadata_field` LEFT JOIN `metadata` ON `metadata`.`field` = `metadata_field`.`id` WHERE `metadata`.`id` IS NULL;');

        $this->subject->collectGarbage();
    }

    public function testGetPropertyListReturnsData(): void
    {
        $id   = 666;
        $name = 'some-name';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `metadata_field`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => (string) $id, 'name' => $name], false);

        static::assertSame(
            [$id => $name],
            iterator_to_array($this->subject->getPropertyList())
        );
    }

    public function testFindByIdReturnsNullIfNoItemWasFound(): void
    {
        $id = 666;

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata_field` WHERE `id` = ?',
                [
                    $id
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, MetadataField::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findById($id)
        );
    }

    public function testFindByIdReturnsFoundItem(): void
    {
        $id = 666;

        $result = $this->createMock(PDOStatement::class);
        $item   = $this->createMock(MetadataField::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata_field` WHERE `id` = ?',
                [
                    $id
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, MetadataField::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn($item);

        static::assertSame(
            $item,
            $this->subject->findById($id)
        );
    }

    public function testFindByNameReturnsNullIfNoItemWasFound(): void
    {
        $name = 'some-name';

        $result = $this->createMock(PDOStatement::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata_field` WHERE `name` = ? LIMIT 1',
                [
                    $name
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, MetadataField::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn(false);

        static::assertNull(
            $this->subject->findByName($name)
        );
    }

    public function testFindByNameReturnsFoundItem(): void
    {
        $name = 'some-name';

        $result = $this->createMock(PDOStatement::class);
        $item   = $this->createMock(MetadataField::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT * FROM `metadata_field` WHERE `name` = ? LIMIT 1',
                [
                    $name
                ],
            )
            ->willReturn($result);

        $result->expects(static::once())
            ->method('setFetchMode')
            ->with(PDO::FETCH_CLASS, MetadataField::class, [$this->subject]);
        $result->expects(static::once())
            ->method('fetch')
            ->willReturn($item);

        static::assertSame(
            $item,
            $this->subject->findByName($name)
        );
    }

    public function testPersistInsertsNewItemIfNew(): void
    {
        $field = $this->createMock(MetadataField::class);

        $name   = 'some-name';
        $public = true;
        $result = 666;

        $field->expects(static::once())
            ->method('isNew')
            ->willReturn(true);
        $field->expects(static::once())
            ->method('getName')
            ->willReturn($name);
        $field->expects(static::once())
            ->method('isPublic')
            ->willReturn($public);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `metadata_field` (`name`, `public`) VALUES (?, ?)',
                [
                    $name,
                    $public
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($result);

        static::assertSame(
            $result,
            $this->subject->persist($field)
        );
    }

    public function testPersistUpdatesExistingItem(): void
    {
        $field = $this->createMock(MetadataField::class);

        $fieldId = 42;
        $name    = 'some-name';
        $public  = true;

        $field->expects(static::once())
            ->method('isNew')
            ->willReturn(false);
        $field->expects(static::once())
            ->method('getName')
            ->willReturn($name);
        $field->expects(static::once())
            ->method('isPublic')
            ->willReturn($public);
        $field->expects(static::once())
            ->method('getId')
            ->willReturn($fieldId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `metadata_field` SET `name` = ?, `public` = ? WHERE `id` = ?',
                [
                    $name,
                    $public,
                    $fieldId
                ]
            );

        static::assertNull(
            $this->subject->persist($field)
        );
    }

    public function testPrototypeReturnsNewItem(): void
    {
        static::assertInstanceOf(
            MetadataField::class,
            $this->subject->prototype()
        );
    }
}
