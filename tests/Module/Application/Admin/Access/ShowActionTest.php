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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AccessRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use ArrayIterator;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    private MockInterface&AccessRepositoryInterface $accessRepository;
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private MockInterface&ModelFactoryInterface $modelFactory;
    private ShowAction $subject;
    private MockInterface&UiInterface $ui;

    public function testRunRendersList(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $access     = $this->mock(Access::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->accessRepository->shouldReceive('getAccessLists')
            ->withNoArgs()
            ->once()
            ->andReturn(new ArrayIterator([$access]));

        // the list template renders each row, so the item's collaborators have to answer
        $user = $this->mock(User::class);
        $this->modelFactory->shouldReceive('createUser')->andReturn($user);
        $user->shouldReceive('isNew')->andReturnFalse();
        $user->shouldReceive('getFullDisplayName')->andReturn('some-user');
        $access->shouldReceive('getName')->andReturn('some-name');
        $access->shouldReceive('getStartIp')->andReturn('1.2.3.4');
        $access->shouldReceive('getEndIp')->andReturn('1.2.3.5');
        $access->shouldReceive('getLevelName')->andReturn('read');
        $access->shouldReceive('getUserName')->andReturn('some-user');
        $access->shouldReceive('getTypeName')->andReturn('interface');
        $access->shouldReceive('getId')->andReturn(666);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui               = $this->mock(UiInterface::class);
        $this->accessRepository = $this->mock(AccessRepositoryInterface::class);
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('some-web-path');

        $this->subject = new ShowAction(
            $this->ui,
            $this->accessRepository,
            $this->modelFactory,
            $this->configContainer
        );
    }
}
