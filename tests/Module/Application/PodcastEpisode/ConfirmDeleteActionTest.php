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

namespace Ampache\Module\Application\PodcastEpisode;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ConfirmDeleteActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private DeletionUrlResolverInterface&MockObject $deletionUrlResolver;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockObject $modelFactory;
    private PodcastDeleterInterface&MockObject $podcastDeleter;
    private ServerRequestInterface&MockObject $request;
    private RequestParserInterface&MockObject $requestParser;
    private ConfirmDeleteAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunReturnsNullInDemoMode(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);

        $this->requestParser->expects(static::never())
            ->method('verifyForm');

        self::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunThrowsIfFormTokenIsInvalid(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(false);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with('delete_podcast_episode')
            ->willReturn(false);
        $this->requestParser->expects(static::never())
            ->method('getFromRequest');
        $this->request->expects(static::never())
            ->method('getQueryParams');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->requestParser       = $this->createMock(RequestParserInterface::class);
        $this->configContainer     = $this->createMock(ConfigContainerInterface::class);
        $this->ui                  = $this->createMock(UiInterface::class);
        $this->modelFactory        = $this->createMock(ModelFactoryInterface::class);
        $this->podcastDeleter      = $this->createMock(PodcastDeleterInterface::class);
        $this->logger              = $this->createMock(LoggerInterface::class);
        $this->deletionUrlResolver = $this->createMock(DeletionUrlResolverInterface::class);
        $this->gatekeeper          = $this->createMock(GuiGatekeeperInterface::class);
        $this->request             = $this->createMock(ServerRequestInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->requestParser,
            $this->configContainer,
            $this->ui,
            $this->modelFactory,
            $this->podcastDeleter,
            $this->logger,
            $this->deletionUrlResolver
        );
    }
}
