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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    private ModelFactoryInterface&MockInterface $modelFactory;

    private UiInterface&MockInterface $ui;

    private LoggerInterface&MockInterface $logger;

    private PrivilegeCheckerInterface&MockInterface $privilegeChecker;

    private ConfigContainerInterface&MockInterface $configContainer;

    private ShowAction $subject;

    protected function setUp(): void
    {
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);
        $this->ui               = $this->mock(UiInterface::class);
        $this->logger           = $this->mock(LoggerInterface::class);
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->subject = new ShowAction(
            $this->modelFactory,
            $this->ui,
            $this->logger,
            $this->privilegeChecker,
            $this->configContainer
        );
    }

    public function testRunShowsErrorIfAlbumDoesNotExist(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 42;

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
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

        $this->logger->shouldReceive('warning')
            ->with(
                'Requested an album that does not exist',
                [LegacyLogger::CONTEXT_TYPE => ShowAction::class]
            )
            ->once();

        $this->expectOutputString('You have requested an object that does not exist');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsAlbumWithGroups(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);
        $isEditAble = true;

        $albumId = 42;

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(2);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_album_group_disks.inc.php',
                [
                    'album' => $album,
                    'isAlbumEditable' => $isEditAble,
                ]
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsAlbumNotEditableIfArtistIsNotSet(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(2);
        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->createExpectations(
            $album,
            $gatekeeper,
            false,
            'show_album_group_disks.inc.php'
        );
    }

    public function testRunShowsAlbumNotEditableIfFeatureDisabled(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnFalse();

        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(2);
        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(123);

        $this->createExpectations(
            $album,
            $gatekeeper,
            false,
            'show_album_group_disks.inc.php'
        );
    }

    public function testRunShowsAlbumEditableIfUsersMatch(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $userId = 42;

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnTrue();

        $album->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(2);
        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(123);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->createExpectations(
            $album,
            $gatekeeper,
            true,
            'show_album_group_disks.inc.php'
        );
    }

    public function testRunShowsAlbumEditableIfContentManager(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(2);

        $this->createExpectations(
            $album,
            $gatekeeper,
            true,
            'show_album_group_disks.inc.php',
        );
    }

    public function testRunShowsAlbumEditabbleWithSingleDisc(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $userId = 42;

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnTrue();

        $album->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $album->shouldReceive('getDiskCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $album->shouldReceive('getAlbumArtist')
            ->withNoArgs()
            ->once()
            ->andReturn(123);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->createExpectations(
            $album,
            $gatekeeper,
            true,
            'show_album.inc.php'
        );
    }

    private function createExpectations(
        MockInterface $album,
        GuiGatekeeperInterface $gatekeeper,
        bool $isEditAble,
        string $templateName
    ): void {
        $request = $this->mock(ServerRequestInterface::class);

        $albumId = 42;

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                $templateName,
                [
                    'album' => $album,
                    'isAlbumEditable' => $isEditAble,
                ]
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
