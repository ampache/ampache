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

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowEditActionTest extends TestCase
{
    private UiInterface&MockObject $ui;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ShoutObjectLoaderInterface&MockObject $shoutObjectLoader;

    private ShoutRepositoryInterface&MockObject $shoutRepository;

    private ShowEditAction $subject;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ServerRequestInterface&MockObject $request;

    protected function setUp(): void
    {
        $this->ui                = $this->createMock(UiInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->shoutObjectLoader = $this->createMock(ShoutObjectLoaderInterface::class);
        $this->shoutRepository   = $this->createMock(ShoutRepositoryInterface::class);

        $this->subject = new ShowEditAction(
            $this->ui,
            $this->modelFactory,
            $this->shoutObjectLoader,
            $this->shoutRepository,
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }

    public function testRunErrorsIfAccessItDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->willReturn(false);

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
        $userId  = 42;

        $shout       = $this->createMock(Shoutbox::class);
        $libraryItem = $this->createMock(library_item::class);
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
            ->method('getUserId')
            ->willReturn($userId);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        static::expectException(ObjectNotFoundException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunRenders(): void
    {
        $shoutId = 666;
        $userId  = 42;

        $shout       = $this->createMock(Shoutbox::class);
        $libraryItem = $this->createMock(library_item::class);
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
            ->method('getUserId')
            ->willReturn($userId);

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with($userId)
            ->willReturn($user);

        $user->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('show')
            ->with(
                'show_edit_shout.inc.php',
                [
                    'shout' => $shout,
                    'object' => $libraryItem,
                    'client' => $user,
                ]
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        $this->subject->run($this->request, $this->gatekeeper);
    }
}
