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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\ShoutboxInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class EditShoutActionTest extends MockeryTestCase
{
    /** @var MockInterface|UiInterface|null */
    private MockInterface $ui;

    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|ShoutRepositoryInterface */
    private MockInterface $shoutRepository;

    private ?EditShoutAction $subject;

    public function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->shoutRepository = $this->mock(ShoutRepositoryInterface::class);

        $this->subject = new EditShoutAction(
            $this->ui,
            $this->configContainer,
            $this->modelFactory,
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

    public function testRunUpdatesEntry(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $shoutbox   = $this->mock(ShoutboxInterface::class);

        $shoutId = 666;
        $webPath = 'some-path';
        $comment = 'some-comment';
        $sticky  = 'true';
        $data    = ['shout_id' => (string) $shoutId, 'comment' => $comment, 'sticky' => $sticky];

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createShoutbox')
            ->with($shoutId)
            ->once()
            ->andReturn($shoutbox);

        $shoutbox->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $shoutbox->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($shoutId);

        $this->shoutRepository->shouldReceive('update')
            ->with(
                $shoutId,
                $comment,
                (bool) $sticky
            )
            ->once();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn($data);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'Shoutbox post has been updated',
                sprintf('%s/admin/shout.php', $webPath)
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
