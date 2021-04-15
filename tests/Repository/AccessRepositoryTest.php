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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Access;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class AccessRepositoryTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var Connection|MockInterface */
    private MockInterface $connection;

    private AccessRepository $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->connection   = $this->mock(Connection::class);

        $this->subject = new AccessRepository(
            $this->modelFactory,
            $this->connection
        );
    }

    public function testGetAccessListsReturnsList(): void
    {
        $result = $this->mock(Result::class);
        $access = $this->mock(Access::class);

        $accessId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with('SELECT `id` FROM `access_list`')
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $accessId, false);

        $this->modelFactory->shouldReceive('createAccess')
            ->with($accessId)
            ->once()
            ->andReturn($access);

        $this->assertSame(
            [$access],
            $this->subject->getAccessLists()
        );
    }

    public function testFindByIPReturnsTrueIfDefaultUserListsExist(): void
    {
        $userIp = '1.2.3.4';
        $level  = 666;
        $type   = 'some-type';

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ? AND `user` = -1',
                [inet_pton($userIp), inet_pton($userIp), $level, $type]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(42);

        $this->assertTrue(
            $this->subject->findByIp($userIp, $level, $type, null)
        );
    }

    public function testFindByIPReturnsFalseIfNothingExistsForSpecificUser(): void
    {
        $userIp = '1.2.3.4';
        $level  = 666;
        $type   = 'some-type';
        $userId = 42;

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ? AND `user` IN (?, -1)',
                [inet_pton($userIp), inet_pton($userIp), $level, $type, $userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->assertFalse(
            $this->subject->findByIp($userIp, $level, $type, $userId)
        );
    }

    public function testDeleteDeletes(): void
    {
        $accessId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `access_list` WHERE `id` = ?',
                [$accessId]
            )
            ->once();

        $this->subject->delete($accessId);
    }

    public function testExistsReturnsTrueOnPositiveRowCount(): void
    {
        $inAddrStart = 'start';
        $inAddrEnd   = 'end';
        $type        = 'some-type';
        $userId      = 666;

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT * FROM `access_list` WHERE `start` = ? AND `end` = ? AND `type` = ? AND `user` = ?',
                [$inAddrStart, $inAddrEnd, $type, $userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(42);

        $this->assertTrue(
            $this->subject->exists(
                $inAddrStart,
                $inAddrEnd,
                $type,
                $userId
            )
        );
    }

    public function testCreateCreates(): void
    {
        $startIp = 'some-start-ip';
        $endIp   = 'some-end-ip';
        $name    = 'some-name';
        $userId  = 666;
        $level   = 42;
        $type    = 'some-type';

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `access_list` (`name`, `level`, `start`, `end`, `user`, `type`) VALUES (?, ?, ?, ?, ?, ?)',
                [$name, $level, $startIp, $endIp, $userId, $type]
            )
            ->once();

        $this->subject->create(
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            $type
        );
    }

    public function testUpdateUpdates(): void
    {
        $accessId = 666;
        $startIp  = 'some-start';
        $endIp    = 'some-end';
        $name     = 'some-name';
        $userId   = 42;
        $level    = 33;
        $type     = 'some-type';

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `access_list` SET `start` = ?, `end` = ?, `level` = ?, `user` = ?, `name` = ?, `type` = ? WHERE `id` = ?',
                [$startIp, $endIp, $level, $userId, $name, $type, $accessId]
            )
            ->once();

        $this->subject->update(
            $accessId,
            $startIp,
            $endIp,
            $name,
            $userId,
            $level,
            $type
        );
    }
}
