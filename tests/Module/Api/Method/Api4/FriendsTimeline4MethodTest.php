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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\User\Activity\UserActivityAccessCheckerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserActivityRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class FriendsTimeline4MethodTest extends MockeryTestCase
{
    private MockInterface|StreamFactoryInterface|null $streamFactory;
    private ?FriendsTimeline4Method $subject;
    private MockInterface|UserActivityAccessCheckerInterface|null $userActivityAccessChecker;
    private MockInterface|UserActivityRepositoryInterface|null $userActivityRepository;

    /**
     * Regression test: this method used to call `getActivities()` (the caller's own timeline) instead of
     * `getFriendsActivities()`, so `friends_timeline` silently returned the caller's own activity.
     */
    public function testHandleReturnsTheCallersFriendsActivityNotTheirOwn(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId  = 42;
        $results = [1, 2, 3];
        $result  = 'some-result';

        AmpConfig::set('sociable', true, true);

        $user->id = $userId;

        $this->userActivityRepository->shouldReceive('getFriendsActivities')
            ->with($userId, 10, 1234)
            ->once()
            ->andReturn($results);
        $this->userActivityRepository->shouldNotReceive('getActivities');

        $this->userActivityAccessChecker->shouldReceive('isVisibleTo')
            ->times(3)
            ->andReturnTrue();

        $output->shouldReceive('timeline')
            ->with(4, $results)
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturn($response);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'limit' => 10,
                    'since' => 1234,
                    'api_format' => 'json',
                    'auth' => 'some-auth',
                ],
                $user,
                4
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->streamFactory             = $this->mock(StreamFactoryInterface::class);
        $this->userActivityRepository    = $this->mock(UserActivityRepositoryInterface::class);
        $this->userActivityAccessChecker = $this->mock(UserActivityAccessCheckerInterface::class);

        $this->subject = new FriendsTimeline4Method(
            $this->userActivityRepository,
            $this->streamFactory,
            $this->userActivityAccessChecker,
        );
    }
}
