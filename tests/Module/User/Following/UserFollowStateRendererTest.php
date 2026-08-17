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
 */

namespace Ampache\Module\User\Following;

use Ampache\Repository\Model\User;
use Ampache\Repository\UserFollowerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserFollowStateRendererTest extends TestCase
{
    private UserFollowStateRenderer $subject;
    private UserFollowerRepositoryInterface&MockObject $userFollowerRepository;

    // NOTE: the full render path is not covered here it requires a bootstrapped global $dic and fails without one.

    public function testRenderReturnsEmptyStringForSameUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(21);

        $this->userFollowerRepository->expects(static::never())
            ->method('isFollowedBy');

        self::assertSame('', $this->subject->render($user, $user));
    }

    protected function setUp(): void
    {
        $this->userFollowerRepository = $this->createMock(UserFollowerRepositoryInterface::class);

        $this->subject = new UserFollowStateRenderer($this->userFollowerRepository);
    }
}
