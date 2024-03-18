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

namespace Ampache\Module\User\Following;

use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserFollowerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserFollowTogglerTest extends TestCase
{
    private UserFollowerRepositoryInterface&MockObject $userFollowerRepository;

    private UserActivityPosterInterface&MockObject $userActivityPoster;

    private UserFollowToggler $subject;

    protected function setUp(): void
    {
        $this->userFollowerRepository = $this->createMock(UserFollowerRepositoryInterface::class);
        $this->userActivityPoster     = $this->createMock(UserActivityPosterInterface::class);

        $this->subject = new UserFollowToggler(
            $this->userFollowerRepository,
            $this->userActivityPoster
        );
    }

    public function testToggleStartsFollowing(): void
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

        $this->userFollowerRepository->expects(static::once())
            ->method('isFollowedBy')
            ->with($user, $followingUser)
            ->willReturn(false);
        $this->userFollowerRepository->expects(static::once())
            ->method('add')
            ->with($user, $followingUser);

        $this->userActivityPoster->expects(static::once())
            ->method('post')
            ->with($followingUserId, 'follow', 'user', $userId, self::isType('int'));

        $this->subject->toggle(
            $user,
            $followingUser
        );
    }

    public function testToggleStopsFollowing(): void
    {
        $user          = $this->createMock(User::class);
        $followingUser = $this->createMock(User::class);

        $this->userFollowerRepository->expects(static::once())
            ->method('isFollowedBy')
            ->with($user, $followingUser)
            ->willReturn(true);
        $this->userFollowerRepository->expects(static::once())
            ->method('delete')
            ->with($user, $followingUser);

        $this->subject->toggle(
            $user,
            $followingUser
        );
    }
}
