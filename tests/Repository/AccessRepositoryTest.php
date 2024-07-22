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

use Ampache\Module\Authorization\Access;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccessRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private ModelFactoryInterface&MockObject $modelFactory;

    private AccessRepository $subject;

    protected function setUp(): void
    {
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new AccessRepository(
            $this->connection,
            $this->modelFactory,
        );
    }

    public function testGetAccessListYieldsData(): void
    {
        $accessId = 666;

        $accessItem = $this->createMock(Access::class);
        $result     = $this->createMock(PDOStatement::class);

        $this->modelFactory->expects(static::once())
            ->method('createAccess')
            ->with($accessId)
            ->willReturn($accessItem);

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT `id` FROM `access_list`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $accessId, false);

        static::assertSame(
            [$accessItem],
            iterator_to_array($this->subject->getAccessLists())
        );
    }

    public function testFindByIpReturnsTrueIfEntryForUserExists(): void
    {
        $userIp = '1.2.3.4';
        $level  = 123;
        $type   = 'some-type';
        $userId = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                sprintf(
                    'SELECT COUNT(`id`) FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ? AND `user` IN (?, %d)',
                    User::INTERNAL_SYSTEM_USER_ID,
                ),
                [inet_pton($userIp), inet_pton($userIp), $level, $type, $userId]
            )
            ->willReturn(123);

        static::assertTrue(
            $this->subject->findByIp($userIp, $level, $type, $userId)
        );
    }

    public function testFindByIpReturnsFalseForNonProvidedUserId(): void
    {
        $userIp = '1.2.3.4';
        $level  = 123;
        $type   = 'some-type';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                sprintf(
                    'SELECT COUNT(`id`) FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ? AND `user` = %d',
                    User::INTERNAL_SYSTEM_USER_ID,
                ),
                [inet_pton($userIp), inet_pton($userIp), $level, $type]
            )
            ->willReturn(0);

        static::assertFalse(
            $this->subject->findByIp($userIp, $level, $type, null)
        );
    }

    public function testFindByIpReturnsFalseForSystemUser(): void
    {
        $userIp = '1.2.3.4';
        $level  = 123;
        $type   = 'some-type';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                sprintf(
                    'SELECT COUNT(`id`) FROM `access_list` WHERE `start` <= ? AND `end` >= ? AND `level` >= ? AND `type` = ? AND `user` = %d',
                    User::INTERNAL_SYSTEM_USER_ID,
                ),
                [inet_pton($userIp), inet_pton($userIp), $level, $type]
            )
            ->willReturn(0);

        static::assertFalse(
            $this->subject->findByIp($userIp, $level, $type, User::INTERNAL_SYSTEM_USER_ID)
        );
    }

    public function testDeleteDeletesAccessItem(): void
    {
        $accessId = 123;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `access_list` WHERE `id` = ?',
                [$accessId]
            );

        $this->subject->delete($accessId);
    }

    public function testExistsReturnsTrueIfItemExists(): void
    {
        $inAddrStart = 'some-ip-start';
        $inAddrEnd   = 'some-ip-end';
        $type        = 'some-type';
        $userId      = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(`id`) FROM `access_list` WHERE `start` = ? AND `end` = ? AND `type` = ? AND `user` = ?',
                [$inAddrStart, $inAddrEnd, $type, $userId]
            )
            ->willReturn(123);

        static::assertTrue(
            $this->subject->exists($inAddrStart, $inAddrEnd, $type, $userId)
        );
    }

    public function testExistsReturnsTrueIfItemDoesNotExist(): void
    {
        $inAddrStart = 'some-ip-start';
        $inAddrEnd   = 'some-ip-end';
        $type        = 'some-type';
        $userId      = 666;

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(`id`) FROM `access_list` WHERE `start` = ? AND `end` = ? AND `type` = ? AND `user` = ?',
                [$inAddrStart, $inAddrEnd, $type, $userId]
            )
            ->willReturn(0);

        static::assertFalse(
            $this->subject->exists($inAddrStart, $inAddrEnd, $type, $userId)
        );
    }

    public function testCreateCreatesItem(): void
    {
        $inAddrStart = 'some-ip-start';
        $inAddrEnd   = 'some-ip-end';
        $type        = 'some-type';
        $userId      = 666;
        $name        = 'some-name';
        $level       = 123;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `access_list` (`name`, `level`, `start`, `end`, `user`, `type`) VALUES (?, ?, ?, ?, ?, ?)',
                [$name, $level, $inAddrStart, $inAddrEnd, $userId, $type]
            );

        $this->subject->create(
            $inAddrStart,
            $inAddrEnd,
            $name,
            $userId,
            $level,
            $type
        );
    }

    public function testUpdateUpdatesItem(): void
    {
        $itemId      = 42;
        $inAddrStart = 'some-ip-start';
        $inAddrEnd   = 'some-ip-end';
        $type        = 'some-type';
        $userId      = 666;
        $name        = 'some-name';
        $level       = 123;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `access_list` SET `start` = ?, `end` = ?, `level` = ?, `user` = ?, `name` = ?, `type` = ? WHERE `id` = ?',
                [$inAddrStart, $inAddrEnd, $level, $userId, $name, $type, $itemId]
            );

        $this->subject->update(
            $itemId,
            $inAddrStart,
            $inAddrEnd,
            $name,
            $userId,
            $level,
            $type
        );
    }
}
