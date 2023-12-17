<?php

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

declare(strict_types=1);

namespace Ampache\Module\User\Following;

use Mockery;
use Ampache\MockeryTestCase;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Mockery\MockInterface;

class UserFollowTogglerTest extends MockeryTestCase
{
    /** @var UserFollowerRepositoryInterface|MockInterface|null */
    private MockInterface $userFollowerRepository;

    /** @var UserActivityPosterInterface|MockInterface|null */
    private MockInterface $userActivityPoster;

    private ?UserFollowToggler $subject;

    protected function setUp(): void
    {
        $this->userFollowerRepository = $this->mock(UserFollowerRepositoryInterface::class);
        $this->userActivityPoster     = $this->mock(UserActivityPosterInterface::class);

        $this->subject = new UserFollowToggler(
            $this->userFollowerRepository,
            $this->userActivityPoster
        );
    }

    public function testToggleStartsFollowing(): void
    {
        $userId          = 666;
        $followingUserId = 42;

        $this->userFollowerRepository->shouldReceive('isFollowedBy')
            ->with($userId, $followingUserId)
            ->once()
            ->andReturnFalse();
        $this->userFollowerRepository->shouldReceive('add')
            ->with($userId, $followingUserId)
            ->once();

        $this->userActivityPoster->shouldReceive('post')
            ->with($followingUserId, 'follow', 'user', $userId, Mockery::type('int'))
            ->once();

        $this->subject->toggle(
            $userId,
            $followingUserId
        );
    }

    public function testToggleStopsFollowing(): void
    {
        $userId          = 666;
        $followingUserId = 42;

        $this->userFollowerRepository->shouldReceive('isFollowedBy')
            ->with($userId, $followingUserId)
            ->once()
            ->andReturnTrue();
        $this->userFollowerRepository->shouldReceive('delete')
            ->with($userId, $followingUserId)
            ->once();

        $this->subject->toggle(
            $userId,
            $followingUserId
        );
    }
}
