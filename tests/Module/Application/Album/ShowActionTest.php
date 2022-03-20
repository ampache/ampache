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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var PrivilegeCheckerInterface|MockInterface|null */
    private MockInterface $privilegeChecker;

    /** @var AlbumRepositoryInterface|MockInterface|null */
    private MockInterface $albumRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private ?ShowAction $subject;

    public function setUp(): void
    {
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);
        $this->ui               = $this->mock(UiInterface::class);
        $this->logger           = $this->mock(LoggerInterface::class);
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $this->albumRepository  = $this->mock(AlbumRepositoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->subject = new ShowAction(
            $this->modelFactory,
            $this->ui,
            $this->logger,
            $this->privilegeChecker,
            $this->albumRepository,
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

        $album->shouldReceive('format')
            ->withNoArgs()
            ->once();
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

        $this->expectOutputString('You have requested an Album that does not exist.');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsAlbumWithGroups(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 42;

        $album->album_suite = [1, 2, 3];

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
                'show_album_group_disks.inc.php',
                [
                    'album' => $album
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
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $album->album_artist = '';

        $this->createExpectations(
            $album,
            $gatekeeper,
            false
        );
    }

    public function testRunShowsAlbumNotEditableIfFeatureDisabled(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnFalse();

        $album->album_artist = 'some-artist';

        $this->createExpectations(
            $album,
            $gatekeeper,
            false
        );
    }

    public function testRunShowsAlbumEditableIfUsersMatch(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $userId = 42;

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::UPLOAD_ALLOW_EDIT)
            ->once()
            ->andReturnTrue();

        $album->album_artist = 'some-artist';

        $album->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->createExpectations(
            $album,
            $gatekeeper,
            true
        );
    }

    public function testRunShowsAlbumEditableIfContentManager(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->createExpectations(
            $album,
            $gatekeeper,
            true
        );
    }

    private function createExpectations(
        MockInterface $album,
        GuiGatekeeperInterface $gatekeeper,
        bool $isEditAble
    ): void {
        $request    = $this->mock(ServerRequestInterface::class);

        $albumId = 42;

        $album->album_suite = [1];

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
                'show_album.inc.php',
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
