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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\User;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IpHistoryRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private ConfigContainerInterface&MockObject $configContainer;

    private IpHistoryRepository $subject;

    protected function setUp(): void
    {
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new IpHistoryRepository(
            $this->connection,
            $this->configContainer,
        );
    }

    public function testGetHistoryReturnsData(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $ip     = '1.2.3.4';
        $date   = time();
        $agent  = 'client agent';
        $userId = 666;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `ip`, `date`, `agent` FROM `ip_history` WHERE `user` = ? AND `date` >= ? GROUP BY `ip`, `date`, `agent` ORDER BY `date` DESC',
                [$userId, $date]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'ip' => inet_pton($ip),
                    'date' => (string) $date,
                    'agent' => (string) $agent,
                ],
                false
            );

        $result = current(
            iterator_to_array($this->subject->getHistory($user))
        );

        static::assertSame(
            $ip,
            $result['ip']
        );
        static::assertSame(
            $date,
            $result['date']->getTimestamp()
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

        static::assertNull(
            $this->subject->getRecentIpForUser($user)
        );
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

        static::assertSame(
            $ip,
            $this->subject->getRecentIpForUser($user)
        );
    }

    public function testCollectGarbageDeletes(): void
    {
        $threshold = 42;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with('user_ip_cardinality')
            ->willReturn((string) $threshold);

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
                'INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`) VALUES (?, ?, ?, ?)',
                [
                    inet_pton($ipAddress),
                    $userId,
                    $date->getTimestamp(),
                    $userAgent
                ]
            );

        $this->subject->create(
            $user,
            $ipAddress,
            $userAgent,
            $date
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
                'INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`) VALUES (?, ?, ?, ?)',
                [
                    '',
                    $userId,
                    $date->getTimestamp(),
                    $userAgent
                ]
            );

        $this->subject->create(
            $user,
            '',
            $userAgent,
            $date
        );
    }
}
