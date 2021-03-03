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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Artist\Deletion\ArtistDeleterInterface;
use Ampache\Module\Artist\Deletion\Exception\ArtistDeletionException;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Artist;
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

    /** @var ArtistDeleterInterface|MockInterface */
    private MockInterface $artistDeleter;

    /** @var MediaDeletionCheckerInterface|MockInterface */
    private MockInterface $mediaDeletionChecker;

    private ConfirmDeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory         = $this->mock(ModelFactoryInterface::class);
        $this->ui                   = $this->mock(UiInterface::class);
        $this->artistDeleter        = $this->mock(ArtistDeleterInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->modelFactory,
            $this->ui,
            $this->artistDeleter,
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
        $request     = $this->mock(ServerRequestInterface::class);
        $gatekeeper  = $this->mock(GuiGatekeeperInterface::class);
        $artist      = $this->mock(Artist::class);

        $artistId = 666;
        $userId   = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(sprintf('Unauthorized to remove the artist `%d`', $artistId));

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['artist_id' => (string) $artistId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($artist, $userId)
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

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
        $request     = $this->mock(ServerRequestInterface::class);
        $gatekeeper  = $this->mock(GuiGatekeeperInterface::class);
        $artist      = $this->mock(Artist::class);

        $artistId = 666;
        $userId   = 42;
        $webPath  = 'some-webpath';

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
                'Couldn\'t delete this Artist.',
                $webPath
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['artist_id' => (string) $artistId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($artist, $userId)
            ->once()
            ->andReturnTrue();

        $this->artistDeleter->shouldReceive('remove')
            ->with($artist)
            ->once()
            ->andThrow(new ArtistDeletionException());

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

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
        $request     = $this->mock(ServerRequestInterface::class);
        $gatekeeper  = $this->mock(GuiGatekeeperInterface::class);
        $artist      = $this->mock(Artist::class);

        $artistId = 666;
        $userId   = 42;
        $webPath  = 'some-webpath';

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
                'The Artist has been deleted',
                $webPath
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['artist_id' => (string) $artistId]);

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($artist, $userId)
            ->once()
            ->andReturnTrue();

        $this->artistDeleter->shouldReceive('remove')
            ->with($artist)
            ->once();

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

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
