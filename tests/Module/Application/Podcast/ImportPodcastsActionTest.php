<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Podcast\Exchange\PodcastOpmlImporterInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ImportPodcastsActionTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $configContainer;

    private UiInterface&MockObject $ui;

    private RequestParserInterface&MockObject $requestParser;

    private CatalogLoaderInterface&MockObject $catalogLoader;

    private PodcastOpmlImporterInterface&MockObject $podcastOpmlImporter;

    private ImportPodcastsAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->configContainer     = $this->createMock(ConfigContainerInterface::class);
        $this->ui                  = $this->createMock(UiInterface::class);
        $this->requestParser       = $this->createMock(RequestParserInterface::class);
        $this->catalogLoader       = $this->createMock(CatalogLoaderInterface::class);
        $this->podcastOpmlImporter = $this->createMock(PodcastOpmlImporterInterface::class);

        $this->subject = new ImportPodcastsAction(
            $this->configContainer,
            $this->ui,
            $this->requestParser,
            $this->catalogLoader,
            $this->podcastOpmlImporter
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
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

    public function testRunThrowsIfAccessLevelIsInsufficient(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfDemoMode(): void
    {
        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfFormVerificationFails(): void
    {
        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, false);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->requestParser->expects(static::once())
            ->method('verifyForm')
            ->with('import_podcasts')
            ->willReturn(false);

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }
}
