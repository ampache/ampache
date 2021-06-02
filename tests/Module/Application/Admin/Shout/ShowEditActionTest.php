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

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutParentObjectLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\ShoutboxInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowEditActionTest extends MockeryTestCase
{
    private MockInterface $ui;

    private MockInterface $modelFactory;

    private MockInterface $shoutParentObjectLoader;

    private ShowEditAction $subject;

    public function setUp(): void
    {
        $this->ui                      = $this->mock(UiInterface::class);
        $this->modelFactory            = $this->mock(ModelFactoryInterface::class);
        $this->shoutParentObjectLoader = $this->mock(ShoutParentObjectLoaderInterface::class);

        $this->subject = new ShowEditAction(
            $this->ui,
            $this->modelFactory,
            $this->shoutParentObjectLoader
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(
                AccessLevelEnum::TYPE_INTERFACE,
                AccessLevelEnum::LEVEL_ADMIN
            )
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $shout      = $this->mock(ShoutboxInterface::class);
        $object     = $this->mock(library_item::class);
        $user       = $this->mock(User::class);

        $shoutId    = 666;
        $userId     = 42;
        $objectId   = 33;
        $objectType = 'some-type';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['shout_id' => (string) $shoutId]);

        $this->modelFactory->shouldReceive('createShoutbox')
            ->with($shoutId)
            ->once()
            ->andReturn($shout);
        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $shout->shouldReceive('getObjectType')
            ->withNoArgs()
            ->once()
            ->andReturn($objectType);
        $shout->shouldReceive('getObjectId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);
        $shout->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(
                AccessLevelEnum::TYPE_INTERFACE,
                AccessLevelEnum::LEVEL_ADMIN
            )
            ->once()
            ->andReturnTrue();

        $this->shoutParentObjectLoader->shouldReceive('load')
            ->with($objectType, $objectId)
            ->once()
            ->andReturn($object);

        $object->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_edit_shout.inc.php',
                [
                    'client' => $user,
                    'object' => $object,
                    'shout' => $shout,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
