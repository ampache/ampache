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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\IpHistoryRepositoryInterface;
use ArrayIterator;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

class ShowIpHistoryActionTest extends MockeryTestCase
{
    private MockInterface&UiInterface $ui;

    private MockInterface&ModelFactoryInterface $modelFactory;

    private MockInterface&IpHistoryRepositoryInterface $ipHistoryRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private ShowIpHistoryAction $subject;

    protected function setUp(): void
    {
        $this->ui                  = $this->mock(UiInterface::class);
        $this->modelFactory        = $this->mock(ModelFactoryInterface::class);
        $this->ipHistoryRepository = $this->mock(IpHistoryRepositoryInterface::class);
        $this->configContainer     = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new ShowIpHistoryAction(
            $this->ui,
            $this->modelFactory,
            $this->ipHistoryRepository,
            $this->configContainer,
        );
    }

    public function testRunShowErrorIfUserDoesNotExist(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->createMock(User::class);

        $userId = -1;

        static::expectException(ObjectNotFoundException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['user_id' => (string) $userId]);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->createMock(User::class);

        $userId       = 666;
        $history      = new ArrayIterator(['some-history']);
        $userFullName = 'some-name';
        $webPath      = 'some-path';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['user_id' => (string) $userId]);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->expects(static::once())
            ->method('get_fullname')
            ->willReturn($userFullName);
        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->ipHistoryRepository->shouldReceive('getHistory')
            ->with($user)
            ->once()
            ->andReturn($history);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showBoxTop')
            ->with(sprintf('%s IP History', $userFullName))
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_ip_history.inc.php',
                [
                    'workingUser' => $user,
                    'history' => $history,
                    'showAll' => false,
                    'webPath' => $webPath,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showBoxBottom')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunRendersCompleteHistory(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->createMock(User::class);

        $userId       = 666;
        $history      = new ArrayIterator(['some-history']);
        $webPath      = 'some-path';
        $userFullName = 'some-name';

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['user_id' => (string) $userId, 'all' => 1]);

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $user->expects(static::once())
            ->method('get_fullname')
            ->willReturn($userFullName);

        $this->ipHistoryRepository->shouldReceive('getHistory')
            ->with($user, 0)
            ->once()
            ->andReturn($history);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showBoxTop')
            ->with(sprintf('%s IP History', $userFullName))
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_ip_history.inc.php',
                [
                    'workingUser' => $user,
                    'history' => $history,
                    'showAll' => true,
                    'webPath' => $webPath,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showBoxBottom')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
