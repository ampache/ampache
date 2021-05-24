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

namespace Ampache\Module\Application\Channel;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ChannelRepositoryInterface;
use Ampache\Repository\Model\Channel;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowCreateActionTest extends MockeryTestCase
{
    private MockInterface $configContainer;

    private MockInterface $ui;

    private MockInterface $modelFactory;

    private MockInterface $channelRepository;

    private ShowCreateAction $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->ui                = $this->mock(UiInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->channelRepository = $this->mock(ChannelRepositoryInterface::class);

        $this->subject = new ShowCreateAction(
            $this->configContainer,
            $this->ui,
            $this->modelFactory,
            $this->channelRepository
        );
    }

    public function testRunReturnsNullIfNotEnabled(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CHANNEL)
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunDoesNothingIfObjectTypeIsNotPlaylist(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CHANNEL)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsCreationForm(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;
        $port       = 42;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CHANNEL)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturnNull();
        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['id' => (string) $playlistId, 'type' => 'playlist']);

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $playlist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->channelRepository->shouldReceive('getNextPort')
            ->with(Channel::DEFAULT_PORT)
            ->once()
            ->andReturn($port);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_add_channel.inc.php',
                [
                    'object' => $playlist,
                    'newPort' => $port
                ]
            )
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
