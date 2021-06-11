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

namespace Ampache\Module\Application\TvShow;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\TvShow\Deletion\TvShowDeleterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\TvShowInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConfirmDeleteActionTest extends MockeryTestCase
{
    private MockInterface $configContainer;

    private MockInterface $ui;

    private MockInterface $modelFactory;

    private MockInterface $mediaDeletionChecker;

    private MockInterface $tvShowDeleter;

    private ConfirmDeleteAction $subject;

    public function setUp(): void
    {
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->ui                   = $this->mock(UiInterface::class);
        $this->modelFactory         = $this->mock(ModelFactoryInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);
        $this->tvShowDeleter        = $this->mock(TvShowDeleterInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->ui,
            $this->modelFactory,
            $this->mediaDeletionChecker,
            $this->tvShowDeleter
        );
    }

    public function testRunReturnsNullOnDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsExceptionIfNotDeletable(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $tvShow     = $this->mock(TvShowInterface::class);

        $tvShowId = 666;
        $userId   = 42;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tvshow_id' => (string) $tvShowId]);

        $this->modelFactory->shouldReceive('createTvShow')
            ->with($tvShowId)
            ->once()
            ->andReturn($tvShow);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($tvShow, $userId)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            sprintf('Unauthorized to remove the tvshow `%d`', $tvShowId),
        );

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunDeletes(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $tvShow     = $this->mock(TvShowInterface::class);

        $tvShowId = 666;
        $userId   = 42;
        $webPath  = 'some-web-path';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tvshow_id' => (string) $tvShowId]);

        $this->modelFactory->shouldReceive('createTvShow')
            ->with($tvShowId)
            ->once()
            ->andReturn($tvShow);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($tvShow, $userId)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->tvShowDeleter->shouldReceive('delete')
            ->with($tvShow)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'No Problem',
                'TV Show has been deleted',
                $webPath
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

    public function testRunErrors(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $tvShow     = $this->mock(TvShowInterface::class);

        $tvShowId = 666;
        $userId   = 42;
        $webPath  = 'some-web-path';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tvshow_id' => (string) $tvShowId]);

        $this->modelFactory->shouldReceive('createTvShow')
            ->with($tvShowId)
            ->once()
            ->andReturn($tvShow);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($tvShow, $userId)
            ->once()
            ->andReturnTrue();

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->tvShowDeleter->shouldReceive('delete')
            ->with($tvShow)
            ->once()
            ->andReturnFalse();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'There Was a Problem',
                'Couldn\'t delete this TV Show',
                $webPath
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
