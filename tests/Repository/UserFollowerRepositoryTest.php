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
use Ampache\Repository\Model\User;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserFollowerRepositoryTest extends TestCase
{
    private DatabaseConnectionInterface&MockObject $connection;

    private UserFollowerRepository $subject;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new UserFollowerRepository(
            $this->connection
        );
    }

    public function testGetFollowersReturnsData(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId = 666;
        $itemId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `user` FROM `user_follower` WHERE `follow_user` = ?',
                [$userId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $itemId, false);

        static::assertSame(
            [$itemId],
            $this->subject->getFollowers($user)
        );
    }

    public function testGetFollowingReturnsData(): void
    {
        $user   = $this->createMock(User::class);
        $result = $this->createMock(PDOStatement::class);

        $userId = 666;
        $itemId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `follow_user` FROM `user_follower` WHERE `user` = ?',
                [$userId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $itemId, false);

        static::assertSame(
            [$itemId],
            $this->subject->getFollowing($user)
        );
    }

    public function testIsFollowedByReturnsTrueIfSo(): void
    {
        $user          = $this->createMock(User::class);
        $followingUser = $this->createMock(User::class);

        $userId          = 666;
        $followingUserId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $followingUser->expects(static::once())
            ->method('getId')
            ->willReturn($followingUserId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT count(`id`) FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
                [$followingUserId, $userId]
            )
            ->willReturn(123);

        static::assertTrue(
            $this->subject->isFollowedBy($user, $followingUser)
        );
    }

    public function testAddAddsEntry(): void
    {
        $user          = $this->createMock(User::class);
        $followingUser = $this->createMock(User::class);

        $userId          = 666;
        $followingUserId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $followingUser->expects(static::once())
            ->method('getId')
            ->willReturn($followingUserId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `user_follower` (`user`, `follow_user`, `follow_date`) VALUES (?, ?, UNIX_TIMESTAMP())',
                [
                    $followingUserId,
                    $userId,
                ]
            );

        $this->subject->add($user, $followingUser);
    }

    public function testDeleteDeletesEntry(): void
    {
        $user          = $this->createMock(User::class);
        $followingUser = $this->createMock(User::class);

        $userId          = 666;
        $followingUserId = 42;

        $user->expects(static::once())
            ->method('getId')
            ->willReturn($userId);

        $followingUser->expects(static::once())
            ->method('getId')
            ->willReturn($followingUserId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
                [
                    $followingUserId,
                    $userId,
                ]
            );

        $this->subject->delete($user, $followingUser);
    }
}
