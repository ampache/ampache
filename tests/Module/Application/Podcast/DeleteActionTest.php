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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class DeleteActionTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $configContainer;
    private DeletionUrlResolverInterface&MockObject $deletionUrlResolver;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private ServerRequestInterface&MockObject $request;
    private DeleteAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunRendersConfirmation(): void
    {
        $podcastId  = 666;
        $burlParam  = 'aA+b/c=';
        $originPage = '/browse.php?action=podcast';

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['podcast_id' => (string) $podcastId, 'burl' => $burlParam]);

        $this->deletionUrlResolver->expects(static::once())
            ->method('resolveBurl')
            ->with($burlParam)
            ->willReturn($originPage);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...$this->withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE]
            ))
            ->willReturn(true, false);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmationWithReturn')
            ->with(
                'Are You Sure?',
                'The Podcast will be removed from the database',
                sprintf(
                    '/podcast.php?action=confirm_delete&podcast_id=%d&burl=aA%%2Bb%%2Fc%%3D',
                    $podcastId
                ),
                $originPage,
                'delete_podcast'
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        self::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunReturnsNullIfPodcastIsDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(false);

        self::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunThrowsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfDemoIsEnabled(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...$this->withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE]
            ))
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->configContainer     = $this->createMock(ConfigContainerInterface::class);
        $this->ui                  = $this->createMock(UiInterface::class);
        $this->deletionUrlResolver = $this->createMock(DeletionUrlResolverInterface::class);

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $this->subject = new DeleteAction(
            $this->configContainer,
            $this->ui,
            $this->deletionUrlResolver
        );
    }
}
