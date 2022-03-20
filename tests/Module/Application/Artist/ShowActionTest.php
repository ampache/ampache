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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
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

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var AlbumRepositoryInterface|MockInterface|null */
    private MockInterface $albumRepository;

    private ?ShowAction $subject;

    public function setUp(): void
    {
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->ui              = $this->mock(UiInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->albumRepository = $this->mock(AlbumRepositoryInterface::class);

        $this->subject = new ShowAction(
            $this->modelFactory,
            $this->configContainer,
            $this->ui,
            $this->logger,
            $this->albumRepository
        );
    }

    public function testRunsShowsErrorIfArtistIsNew(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $artist     = $this->mock(Artist::class);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with(0)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('warning')
            ->with(
                'Requested an artist that does not exist',
                [LegacyLogger::CONTEXT_TYPE => ShowAction::class]
            )
            ->once();

        $this->expectOutputString('You have requested an Artist that does not exist.');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunsOutputsGroupedAlbums(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $artist     = $this->mock(Artist::class);

        $artistId         = 666;
        $catalogId        = 42;
        $multi_object_ids = ['some-ids'];

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
                'show_artist.inc.php',
                [
                    'multi_object_ids' => $multi_object_ids,
                    'object_ids' => null,
                    'object_type' => 'album',
                    'artist' => $artist,
                    'gatekeeper' => $gatekeeper,
                ]
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'artist' => (string) $artistId,
                'catalog' => (string) $catalogId,
            ]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALBUM_RELEASE_TYPE)
            ->once()
            ->andReturnTrue();

        $this->albumRepository->shouldReceive('getByArtist')
            ->with($artistId, $catalogId, true)
            ->once()
            ->andReturn($multi_object_ids);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunsOutputsUngroupedAlbums(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $artist     = $this->mock(Artist::class);

        $artistId   = 666;
        $catalogId  = 42;
        $object_ids = ['some-ids'];

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
                'show_artist.inc.php',
                [
                    'multi_object_ids' => null,
                    'object_ids' => $object_ids,
                    'object_type' => 'album',
                    'artist' => $artist,
                    'gatekeeper' => $gatekeeper,
                ]
            )
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'artist' => (string) $artistId,
                'catalog' => (string) $catalogId,
            ]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with($artistId)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALBUM_RELEASE_TYPE)
            ->once()
            ->andReturnFalse();

        $this->albumRepository->shouldReceive('getByArtist')
            ->with($artistId, $catalogId)
            ->once()
            ->andReturn($object_ids);

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
