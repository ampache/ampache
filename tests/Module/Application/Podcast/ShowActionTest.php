<?php

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

declare(strict_types=1);

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;

    private UiInterface&MockObject $ui;

    private LoggerInterface&MockObject $logger;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    private ShowAction $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $this->subject = new ShowAction(
            $this->configContainer,
            $this->ui,
            $this->logger,
            $this->modelFactory,
        );
    }

    public function testRunReturnsNullIfPodcastIsDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(false);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunShowErrorIfPodcastDoesNotExist(): void
    {
        $podcast = $this->createMock(Podcast::class);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn([]);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with(0)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                'Requested a podcast that does not exist',
                [LegacyLogger::CONTEXT_TYPE => $this->subject::class]
            );

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        static::expectOutputString('You have requested an object that does not exist');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunRenders(): void
    {
        $podcast = $this->createMock(Podcast::class);

        $name        = 'some-name';
        $episodeList = [123, 456];

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn([]);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with(0)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(false);
        $podcast->expects(static::once())
            ->method('format');
        $podcast->expects(static::once())
            ->method('get_fullname')
            ->willReturn($name);
        $podcast->expects(static::once())
            ->method('get_episodes')
            ->willReturn($episodeList);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('show')
            ->with(
                'show_podcast.inc.php',
                [
                    'podcast' => $podcast,
                    'object_ids' => $episodeList,
                    'object_type' => 'podcast_episode'
                ]
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
