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

namespace Ampache\Module\Api\Ajax\Handler\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FlipFollowActionTest extends MockeryTestCase
{
    private MockInterface $configContainer;

    private MockInterface $privilegechecker;

    private MockInterface $modelFactory;

    private MockInterface $userFollowToggler;

    private MockInterface $userFollowStateRenderer;

    private FlipFollowAction $subject;

    public function setUp(): void
    {
        $this->configContainer         = $this->mock(ConfigContainerInterface::class);
        $this->privilegechecker        = $this->mock(PrivilegeCheckerInterface::class);
        $this->modelFactory            = $this->mock(ModelFactoryInterface::class);
        $this->userFollowToggler       = $this->mock(UserFollowTogglerInterface::class);
        $this->userFollowStateRenderer = $this->mock(UserFollowStateRendererInterface::class);

        $this->subject = new FlipFollowAction(
            $this->configContainer,
            $this->privilegechecker,
            $this->modelFactory,
            $this->userFollowToggler,
            $this->userFollowStateRenderer
        );
    }

    public function testHandleReturnsEmptyArrayIfPrivilegeCheckFails(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $user     = $this->mock(User::class);

        $this->privilegechecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->handle(
                $request,
                $response,
                $user
            )
        );
    }

    public function testHandleReturnsEmptyArrayIfSocialIsDisabled(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $user     = $this->mock(User::class);

        $this->privilegechecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->handle(
                $request,
                $response,
                $user
            )
        );
    }

    public function testHandleFollowsAndReturnsContent(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $user       = $this->mock(User::class);
        $followUser = $this->mock(User::class);

        $userId          = 666;
        $followUserId    = 42;
        $renderedContent = 'some-content';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['user_id' => (string) $followUserId]);

        $this->modelFactory->shouldReceive('createUser')
            ->with($followUserId)
            ->once()
            ->andReturn($followUser);

        $this->privilegechecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_USER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SOCIABLE)
            ->once()
            ->andReturnTrue();

        $followUser->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($followUserId);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->userFollowStateRenderer->shouldReceive('render')
            ->with($followUserId, $userId)
            ->once()
            ->andReturn($renderedContent);

        $this->userFollowToggler->shouldReceive('toggle')
            ->with($followUserId, $userId)
            ->once();

        $this->assertSame(
            [
                'button_follow_' . $followUserId => $renderedContent
            ],
            $this->subject->handle(
                $request,
                $response,
                $user
            )
        );
    }
}
