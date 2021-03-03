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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Album\Deletion\AlbumDeleterInterface;
use Ampache\Module\Album\Deletion\Exception\AlbumDeletionException;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var AlbumDeleterInterface|MockInterface */
    private MockInterface $albumDeleter;

    /** @var MediaDeletionCheckerInterface|MockInterface */
    private MockInterface $mediaDeletionChecker;

    private ConfirmDeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory         = $this->mock(ModelFactoryInterface::class);
        $this->ui                   = $this->mock(UiInterface::class);
        $this->albumDeleter         = $this->mock(AlbumDeleterInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->modelFactory,
            $this->ui,
            $this->albumDeleter,
            $this->mediaDeletionChecker
        );
    }

    public function testRunReturnsNullOnDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }

    public function testRunThrowsExceptionIfMediaItemIsNotDeletable(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 666;
        $userId  = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(sprintf('Unauthorized to remove the album `%d`', $albumId));

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album_id' => (string) $albumId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($album, $userId)
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunReturnsErrorIfDeletionFails(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 666;
        $userId  = 42;
        $webPath = 'some-webpath';

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'There Was a Problem',
                'Couldn\'t delete this Album.',
                $webPath
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album_id' => (string) $albumId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($album, $userId)
            ->once()
            ->andReturnTrue();

        $this->albumDeleter->shouldReceive('delete')
            ->with($album)
            ->once()
            ->andThrow(new AlbumDeletionException());

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }

    public function testRunDeletesAndShowConfirmation(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 666;
        $userId  = 42;
        $webPath = 'some-webpath';

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'The Album has been deleted',
                $webPath
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album_id' => (string) $albumId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($album, $userId)
            ->once()
            ->andReturnTrue();

        $this->albumDeleter->shouldReceive('delete')
            ->with($album)
            ->once();

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }
}
