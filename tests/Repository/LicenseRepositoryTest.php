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
use Ampache\Repository\Model\License;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class LicenseRepositoryTest extends TestCase
{
    use ConsecutiveParams;
    use RepositoryTestTrait;

    private DatabaseConnectionInterface&MockObject $connection;

    private LicenseRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new LicenseRepository(
            $this->connection
        );
    }

    public function testGetListReturnsData(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $rowId   = 666;
        $rowName = 'some-name';

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id`, `name` FROM `license` ORDER BY `name`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => (string) $rowId, 'name' => $rowName], false);

        static::assertSame(
            [$rowId => $rowName],
            iterator_to_array(
                $this->subject->getList()
            )
        );
    }

    public function testFindFindObjectByName(): void
    {
        $value  = 'some-name';
        $result = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with('SELECT `id` FROM `license` WHERE `name` = ? LIMIT 1')
            ->willReturn((string) $result);

        static::assertSame(
            $result,
            $this->subject->find($value)
        );
    }

    public function testFindFindObjectByExternalLink(): void
    {
        $value  = 'some-name';
        $result = 666;

        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with(...self::withConsecutive(
                ['SELECT `id` FROM `license` WHERE `name` = ? LIMIT 1'],
                ['SELECT `id` FROM `license` WHERE `external_link` = ? LIMIT 1']
            ))
            ->willReturn(false, (string) $result);

        static::assertSame(
            $result,
            $this->subject->find($value)
        );
    }

    public function testFindReturnsNullIfNothingWasFound(): void
    {
        $value  = 'some-name';

        $this->connection->expects(static::exactly(2))
            ->method('fetchOne')
            ->with(...self::withConsecutive(
                ['SELECT `id` FROM `license` WHERE `name` = ? LIMIT 1'],
                ['SELECT `id` FROM `license` WHERE `external_link` = ? LIMIT 1']
            ))
            ->willReturn(false);

        static::assertNull(
            $this->subject->find($value)
        );
    }

    public function testFindByIdPerformsTest(): void
    {
        $this->runFindByIdTrait(
            'license',
            License::class,
            [$this->subject]
        );
    }

    public function testPersistCreatesNewItem(): void
    {
        $license = $this->createMock(License::class);

        $licenseId    = 666;
        $name         = 'some-name';
        $description  = 'some-description';
        $externalLink = 'some-link';

        $license->expects(static::once())
            ->method('getName')
            ->willReturn($name);
        $license->expects(static::once())
            ->method('getDescription')
            ->willReturn($description);
        $license->expects(static::once())
            ->method('getExternalLink')
            ->willReturn($externalLink);
        $license->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `license` (`name`, `description`, `external_link`) VALUES (?, ?, ?)',
                [
                    $name,
                    $description,
                    $externalLink
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($licenseId);

        static::assertSame(
            $licenseId,
            $this->subject->persist($license)
        );
    }

    public function testPersistUpdatesExistingItems(): void
    {
        $license = $this->createMock(License::class);

        $licenseId    = 666;
        $name         = 'some-name';
        $description  = 'some-description';
        $externalLink = 'some-link';

        $license->expects(static::once())
            ->method('getName')
            ->willReturn($name);
        $license->expects(static::once())
            ->method('getDescription')
            ->willReturn($description);
        $license->expects(static::once())
            ->method('getExternalLink')
            ->willReturn($externalLink);
        $license->expects(static::once())
            ->method('isNew')
            ->willReturn(false);
        $license->expects(static::once())
            ->method('getId')
            ->willReturn($licenseId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ? WHERE `id` = ?',
                [
                    $name,
                    $description,
                    $externalLink,
                    $licenseId
                ]
            );

        static::assertNull(
            $this->subject->persist($license)
        );
    }
}
