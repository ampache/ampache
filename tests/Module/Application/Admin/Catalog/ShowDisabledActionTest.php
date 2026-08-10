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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\SongRepositoryInterface;
use ArrayIterator;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ShowDisabledActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private ServerRequestInterface&MockObject $request;
    private SongRepositoryInterface&MockObject $songRepository;
    private ShowDisabledAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunRendersDisabledSongs(): void
    {
        $song = $this->createMock(Song::class);
        $song->method('getId')->willReturn(666);
        $song->method('get_fullname')->willReturn('Rock & Roll');
        $song->method('get_album_fullname')->willReturn('some-album');
        $song->method('get_parent_fullname')->willReturn('some-artist');
        $song->method('getFile')->willReturn('/some/file.mp3');
        $song->method('getAdditionTime')->willReturn(new DateTimeImmutable('@1700000000'));

        $songs = new ArrayIterator([$song]);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(false);

        $this->songRepository->expects(static::once())
            ->method('getDisabled')
            ->willReturn($songs);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showFooter');
        $this->ui->expects(static::once())
            ->method('showQueryStats');

        ob_start();

        try {
            $this->subject->run($this->request, $this->gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        static::assertStringContainsString('value="enable_disabled"', $output);
        static::assertStringContainsString('Rock &amp; Roll', $output);
    }

    public function testRunShowsEmptyContentOnDemoMode(): void
    {
        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showFooter');
        $this->ui->expects(static::once())
            ->method('showQueryStats');

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->ui              = $this->createMock(UiInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->songRepository  = $this->createMock(SongRepositoryInterface::class);

        $this->subject = new ShowDisabledAction(
            $this->ui,
            $this->configContainer,
            $this->songRepository,
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }
}
