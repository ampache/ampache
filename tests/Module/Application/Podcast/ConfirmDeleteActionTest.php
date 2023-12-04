<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class ConfirmDeleteActionTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $configContainer;

    private UiInterface&MockObject $ui;

    private ModelFactoryInterface&MockObject $modelFactory;

    private ConfirmDeleteAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new ConfirmDeleteAction(
            $this->configContainer,
            $this->ui,
            $this->modelFactory,
        );

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
    }

    public function testRunReturnsNullIfPodcastNotEnabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(false);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunErrorsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunErrorsInDemoMode(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...$this->withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, true);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(true);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunShowsErrorsIfRemovalFails(): void
    {
        $podcastId = 666;
        $webPath   = 'some-path';

        $podcast = $this->createMock(Podcast::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...$this->withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, false);
        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['podcast_id' => (string) $podcastId]);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('remove')
            ->willReturn(false);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'There Was a Problem',
                'Couldn\'t delete this Podcast.',
                sprintf(
                    '%s/browse.php?action=podcast',
                    $webPath
                )
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    public function testRunConfirmsRemoval(): void
    {
        $podcastId = 666;
        $webPath   = 'some-path';

        $podcast = $this->createMock(Podcast::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...$this->withConsecutive(
                [ConfigurationKeyEnum::PODCAST],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, false);
        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['podcast_id' => (string) $podcastId]);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('remove')
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'No Problem',
                'Podcast has been deleted',
                sprintf(
                    '%s/browse.php?action=podcast',
                    $webPath
                )
            );
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
