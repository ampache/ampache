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

namespace Ampache\Module\Application\PlaylistFolder;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderTreeFormatterInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

class CreateActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private RequestParserInterface&MockInterface $requestParser;
    private CreateAction $subject;
    private PlaylistFolderTreeFormatterInterface&MockInterface $treeFormatter;
    private UiInterface&MockInterface $ui;

    public function testRunAddsErrorOnInvalidName(): void
    {
        $user = $this->mock(User::class);

        $this->guardsPass($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('name')->andReturn('');
        $this->requestParser->shouldReceive('getFromRequest')->with('parent')->andReturn('0');

        $this->playlistFolderRepository->shouldReceive('create')->never();
        $this->treeFormatter->shouldReceive('flatten')->with($user)->andReturn([]);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertTrue(AmpError::occurred());
        self::assertSame('Name is required', AmpError::get('name'));
    }

    public function testRunAddsErrorWhenNameIsTakenBySibling(): void
    {
        $user = $this->mock(User::class);

        $this->guardsPass($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('name')->andReturn('Rock');
        $this->requestParser->shouldReceive('getFromRequest')->with('parent')->andReturn('0');

        $this->playlistFolderRepository->shouldReceive('create')->once()->andReturn(null);
        $this->treeFormatter->shouldReceive('flatten')->with($user)->andReturn([]);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertTrue(AmpError::occurred());
        self::assertSame('That name already exists', AmpError::get('name'));
    }

    public function testRunCreatesFolderOnValidInput(): void
    {
        $user = $this->mock(User::class);

        $this->guardsPass($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('name')->andReturn('Rock');
        $this->requestParser->shouldReceive('getFromRequest')->with('parent')->andReturn('0');

        $this->playlistFolderRepository->shouldReceive('create')->with($user, 'Rock', 0)->once()->andReturn(5);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showConfirmation')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertFalse(AmpError::occurred());
    }

    public function testRunThrowsInDemoMode(): void
    {
        self::expectException(AccessDeniedException::class);

        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($this->mock(User::class));
        $this->configContainer->shouldReceive('isFeatureEnabled')->with(ConfigurationKeyEnum::DEMO_MODE)->andReturn(true);
        $this->requestParser->shouldReceive('verifyForm')->never();

        $this->subject->run($this->request(), $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->configContainer           = $this->mock(ConfigContainerInterface::class);
        $this->gatekeeper                = $this->mock(GuiGatekeeperInterface::class);
        $this->playlistFolderRepository  = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->treeFormatter             = $this->mock(PlaylistFolderTreeFormatterInterface::class);
        $this->requestParser             = $this->mock(RequestParserInterface::class);
        $this->ui                        = $this->mock(UiInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('https://ampache.test');

        $this->subject = new CreateAction(
            $this->configContainer,
            $this->playlistFolderRepository,
            $this->treeFormatter,
            $this->requestParser,
            $this->ui,
        );

        // AmpError's state is a private static with no public reset, so reset it here or a failure in one test leaks into the next
        new ReflectionProperty(AmpError::class, 'errors')->setValue(null, []);
        new ReflectionProperty(AmpError::class, 'state')->setValue(null, false);
    }

    private function guardsPass(User&MockInterface $user): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->configContainer->shouldReceive('isFeatureEnabled')->with(ConfigurationKeyEnum::DEMO_MODE)->andReturn(false);
        $this->requestParser->shouldReceive('verifyForm')->with('add_playlist_folder')->andReturn(true);
    }

    private function request(): ServerRequestInterface&MockInterface
    {
        return $this->mock(ServerRequestInterface::class);
    }
}
