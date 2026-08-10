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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IpHistoryRepositoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DatabaseConnectionInterface&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private IpHistoryRepository $subject;

    public function testCollectGarbageDeletes(): void
    {
        $threshold = 42;

        $this->configContainer->expects(static::once())
            ->method('getInt')
            ->with('user_ip_cardinality')
            ->willReturn($threshold);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `ip_history` WHERE `date` < `date` - ?',
                [
                    86400 * $threshold
                ]
            );

        $this->subject->collectGarbage();
    }

    public function testCreateCreatesEntry(): void
    {
        $user = $this->createMock(User::class);

        $ipAddress = '1.2.3.4';
        $userAgent = 'hopefully-no-macos-x';
        $userId    = 666;
        $date      = new DateTime();

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`, `action`) VALUES (?, ?, ?, ?, ?)',
                [
                    inet_pton($ipAddress),
                    $userId,
                    $date->getTimestamp(),
                    $userAgent,
                    'login'
                ]
            );

        $this->subject->create(
            $user,
            $ipAddress,
            $userAgent,
            $date,
            'login'
        );
    }

    public function testCreateCreatesEntryWithEmptyIpAddress(): void
    {
        $user = $this->createMock(User::class);

        $userAgent = 'hopefully-no-macos-x';
        $userId    = 666;
        $date      = new DateTime();

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`, `action`) VALUES (?, ?, ?, ?, ?)',
                [
                    '',
                    $userId,
                    $date->getTimestamp(),
                    $userAgent,
                    'login'
                ]
            );

        $this->subject->create(
            $user,
            '',
            $userAgent,
            $date,
            'login'
        );
    }

    public function testGetHistoryReturnsEmptyIpForNullIpRow(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;
        $date   = time();

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->configContainer->expects(static::once())
            ->method('getInt')
            ->with('user_ip_cardinality', 42)
            ->willReturn(42);

        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects(static::exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'ip' => null,
                    'date' => $date,
                    'agent' => 'legacy-agent',
                    'action' => 'login',
                ],
                false
            );

        $this->connection->expects(static::once())
            ->method('query')
            ->willReturn($statement);

        $result = iterator_to_array($this->subject->getHistory($user));

        self::assertSame('', $result[0]['ip']);
        self::assertSame('legacy-agent', $result[0]['agent']);
    }

    public function testGetRecipientForUserReturnsIp(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;
        $ip     = '1.2.3.4';

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `ip` FROM `ip_history` WHERE `user` = ? ORDER BY `date` DESC LIMIT 1',
                [
                    $userId,
                ]
            )
            ->willReturn(inet_pton($ip));

        self::assertSame(
            $ip,
            $this->subject->getRecentIpForUser($user)
        );
    }

    public function testGetRecipientForUserReturnsNullIfIpIsNull(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `ip` FROM `ip_history` WHERE `user` = ? ORDER BY `date` DESC LIMIT 1',
                [
                    $userId,
                ]
            )
            ->willReturn(null);

        self::assertNull(
            $this->subject->getRecentIpForUser($user)
        );
    }

    public function testGetRecipientForUserReturnsNullIfIpWasNotAvailable(): void
    {
        $user = $this->createMock(User::class);

        $userId = 666;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `ip` FROM `ip_history` WHERE `user` = ? ORDER BY `date` DESC LIMIT 1',
                [
                    $userId,
                ]
            )
            ->willReturn(false);

        self::assertNull(
            $this->subject->getRecentIpForUser($user)
        );
    }

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new IpHistoryRepository(
            $this->connection,
            $this->configContainer,
            $this->logger,
        );
    }
}
