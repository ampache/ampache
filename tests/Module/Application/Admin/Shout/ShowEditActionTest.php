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

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowEditActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private ServerRequestInterface&MockObject $request;
    private ShoutObjectLoaderInterface&MockObject $shoutObjectLoader;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private ShowEditAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunErrorsIfAccessItDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunErrorsIfShoutObjectCannotBeDisplayed(): void
    {
        $shoutId = 666;

        $shout = $this->createMock(Shoutbox::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['shout_id' => (string) $shoutId]);

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn($shout);

        $this->shoutObjectLoader->expects(static::once())
            ->method('loadByShout')
            ->with($shout)
            ->willReturn($this->createMock(library_item::class));

        static::expectException(ObjectNotFoundException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunErrorsIfShoutObjectWasNotFound(): void
    {
        $shoutId = 666;

        $shout = $this->createMock(Shoutbox::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['shout_id' => (string) $shoutId]);

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn($shout);

        static::expectException(ObjectNotFoundException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunErrorsIfShoutUserWasNotFound(): void
    {
        $shoutId = 666;

        $shout       = $this->createMock(Shoutbox::class);
        $libraryItem = $this->createMockForIntersectionOfInterfaces([library_item::class, displayable_item::class]);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['shout_id' => (string) $shoutId]);

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn($shout);

        $this->shoutObjectLoader->expects(static::once())
            ->method('loadByShout')
            ->with($shout)
            ->willReturn($libraryItem);

        $shout->expects(static::once())
            ->method('getUser')
            ->willReturn(null);

        static::expectException(ObjectNotFoundException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunErrorsIfShoutWasNotFound(): void
    {
        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        static::expectException(ObjectNotFoundException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunRenders(): void
    {
        $shoutId = 666;

        $shout       = $this->createMock(Shoutbox::class);
        $libraryItem = $this->createMockForIntersectionOfInterfaces([library_item::class, displayable_item::class]);
        $user        = $this->createMock(User::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['shout_id' => (string) $shoutId]);

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn($shout);

        $this->shoutObjectLoader->expects(static::once())
            ->method('loadByShout')
            ->with($shout)
            ->willReturn($libraryItem);

        $shout->expects(static::once())
            ->method('getUser')
            ->willReturn($user);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $shout->method('getId')->willReturn($shoutId);
        $shout->method('getText')->willReturn('some-text');
        $libraryItem->method('get_f_link')->willReturn('some-object-link');
        $user->method('get_f_link')->willReturn('some-user-link');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        ob_start();

        try {
            $this->subject->run($this->request, $this->gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        static::assertStringContainsString('some-text', $output);
        static::assertStringContainsString('some-object-link', $output);
    }

    protected function setUp(): void
    {
        $this->ui                = $this->createMock(UiInterface::class);
        $this->shoutObjectLoader = $this->createMock(ShoutObjectLoaderInterface::class);
        $this->shoutRepository   = $this->createMock(ShoutRepositoryInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new ShowEditAction(
            $this->ui,
            $this->shoutObjectLoader,
            $this->shoutRepository,
            $this->configContainer,
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }
}
