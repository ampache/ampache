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
 */

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowImportPodcastsActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;

    private UiInterface&MockObject $ui;

    private ShowImportPodcastsAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);

        $this->subject = new ShowImportPodcastsAction(
            $this->configContainer,
            $this->ui
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }

    public function testRunReturnsNullIfPodcastsAreDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(false);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunThrowsIsAccessIsDenied(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(false);

        static::expectException(AccessDeniedException::class);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunRenders(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showBoxTop')
            ->with('Import Podcasts', 'box box_add_podcast');
        $this->ui->expects(static::once())
            ->method('show')
            ->with(
                'show_import_podcasts.inc.php',
                [
                    'catalogId' => 0,
                ]
            );
        $this->ui->expects(static::once())
            ->method('showBoxBottom');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
