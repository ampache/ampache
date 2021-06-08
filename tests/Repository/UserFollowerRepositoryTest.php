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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class UserFollowerRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private UserFollowerRepository $subject;

    public function setUp(): void
    {
        $this->database = $this->mock(Connection::class);

        $this->subject = new UserFollowerRepository(
            $this->database
        );
    }

    public function testGetFollowersReturnsList(): void
    {
        $userId     = 666;
        $followerId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `user` FROM `user_follower` WHERE `follow_user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $followerId, false);

        $this->assertSame(
            [$followerId],
            $this->subject->getFollowers($userId)
        );
    }

    public function testGetFollowingReturnsList(): void
    {
        $userId     = 666;
        $followerId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `follow_user` FROM `user_follower` WHERE `user` = ?',
                [$userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $followerId, false);

        $this->assertSame(
            [$followerId],
            $this->subject->getFollowing($userId)
        );
    }

    public function testIsFollowedByReturnsTrueIfFound(): void
    {
        $userId          = 666;
        $followingUserId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
                [$followingUserId, $userId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(123);

        $this->assertTrue(
            $this->subject->isFollowedBy($userId, $followingUserId)
        );
    }

    public function testAddAdds(): void
    {
        $userId          = 666;
        $followingUserId = 42;
        $time            = 123456;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `user_follower` (`user`, `follow_user`, `follow_date`) VALUES (?, ?, ?)',
                [
                    $followingUserId,
                    $userId,
                    $time
                ]
            )
            ->once();

        $this->subject->add($userId, $followingUserId, $time);
    }

    public function testDeleteDeletes(): void
    {
        $userId          = 666;
        $followingUserId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?',
                [
                    $followingUserId,
                    $userId,
                ]
            )
            ->once();

        $this->subject->delete($userId, $followingUserId);
    }
}
