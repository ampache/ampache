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
use Ampache\Repository\Model\LicenseInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class LicenseRepositoryTest extends MockeryTestCase
{
    /** @var Connection|MockInterface */
    private MockInterface $connection;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    private LicenseRepository $subject;

    public function setUp(): void
    {
        $this->connection   = $this->mock(Connection::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new LicenseRepository(
            $this->connection,
            $this->modelFactory
        );
    }

    public function testGetAllReturnsListOfLicenseObjects(): void
    {
        $licenseId = 666;

        $license   = $this->mock(LicenseInterface::class);
        $result    = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with('SELECT `id` from `license` ORDER BY `name`')
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $licenseId, false);

        $this->modelFactory->shouldReceive('createLicense')
            ->with($licenseId)
            ->once()
            ->andReturn($license);

        $this->assertSame(
            [$license],
            $this->subject->getAll()
        );
    }

    public function testCreateCreatesAndReturnsInsertedId(): void
    {
        $name         = 'some-name';
        $description  = 'some-description';
        $externalLink = 'some-external-link';
        $insertId     = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `license` (`name`, `description`, `external_link`) VALUES (? , ?, ?)',
                [$name, $description, $externalLink]
            )
            ->once();
        $this->connection->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $insertId);

        $this->assertSame(
            $insertId,
            $this->subject->create($name, $description, $externalLink)
        );
    }

    public function testDeleteDeletes(): void
    {
        $licenseId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `license` WHERE `id` = ?',
                [$licenseId]
            )
            ->once();

        $this->subject->delete($licenseId);
    }

    public function testFindReturnsNullIfNothingWasFound(): void
    {
        $searchValue = 'some-search-value';

        $this->connection->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` from `license` WHERE `name` = ?',
                [$searchValue]
            )
            ->once()
            ->andReturnFalse();
        $this->connection->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` from `license` WHERE `external_link` = ?',
                [$searchValue]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->find($searchValue)
        );
    }

    public function testFindReturnsValueFromLinkSearch(): void
    {
        $searchValue = 'some-search-value';
        $licenseId   = 666;

        $this->connection->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` from `license` WHERE `name` = ?',
                [$searchValue]
            )
            ->once()
            ->andReturnFalse();
        $this->connection->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` from `license` WHERE `external_link` = ?',
                [$searchValue]
            )
            ->once()
            ->andReturn((string) $licenseId);

        $this->assertSame(
            $licenseId,
            $this->subject->find($searchValue)
        );
    }

    public function testFindReturnsValueFromNameSearch(): void
    {
        $searchValue = 'some-search-value';
        $licenseId   = 666;

        $this->connection->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` from `license` WHERE `name` = ?',
                [$searchValue]
            )
            ->once()
            ->andReturn((string) $licenseId);

        $this->assertSame(
            $licenseId,
            $this->subject->find($searchValue)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $result    = ['some' => 'data'];
        $licenseId = 666;

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `license` WHERE `id` = ?',
                [$licenseId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDataById($licenseId)
        );
    }

    public function testGetDataByIdReturnsEmptyArrayIfEntryWasNotFound(): void
    {
        $licenseId = 666;

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `license` WHERE `id` = ?',
                [$licenseId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($licenseId)
        );
    }
}
