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
 */

namespace Ampache\Module\Application\Admin\Shout;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\ShoutRepositoryInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

class EditShoutActionTest extends MockeryTestCase
{
    private MockInterface&UiInterface $ui;

    private MockObject&ShoutRepositoryInterface $shoutRepository;

    private EditShoutAction $subject;

    protected function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->shoutRepository = $this->createMock(ShoutRepositoryInterface::class);

        $this->subject = new EditShoutAction(
            $this->ui,
            $this->shoutRepository
        );
    }

    public function testRunThrowExceptionIfAccessIsDenied(): void
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

    public function testRunUpdatesErrorsIfShoutWasNotFound(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $shoutId = 666;

        static::expectException(ObjectNotFoundException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn(null);

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'shout_id' => (string)$shoutId,
            ]);

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunUpdatesEntry(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $shout      = $this->createMock(Shoutbox::class);

        $shoutId = 666;
        $comment = 'some-comment';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->shoutRepository->expects(static::once())
            ->method('findById')
            ->with($shoutId)
            ->willReturn($shout);

        $shout->expects(static::once())
            ->method('setText')
            ->with($comment)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('setSticky')
            ->with(true)
            ->willReturnSelf();
        $shout->expects(static::once())
            ->method('save');

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'shout_id' => (string)$shoutId,
                'comment' => $comment,
                'sticky' => 'on',
            ]);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Shoutbox post has been updated',
                'admin/shout.php'
            )
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
