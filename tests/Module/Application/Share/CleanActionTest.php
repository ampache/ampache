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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ShareRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class CleanActionTest extends TestCase
{
    use ConsecutiveParams;

    private ConfigContainerInterface&MockObject $configContainer;

    private UiInterface&MockObject $ui;

    private ShareRepositoryInterface&MockObject $shareRepository;

    private CleanAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);
        $this->shareRepository = $this->createMock(ShareRepositoryInterface::class);

        $this->request    = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $this->subject = new CleanAction(
            $this->configContainer,
            $this->ui,
            $this->shareRepository
        );
    }

    public function testRunThrowsIfSharingIsDisabled(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunThrowsIfDemoModeIsEnabled(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::SHARE],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, true);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    public function testRunRenders(): void
    {
        $webPath = 'some-path';

        $this->configContainer->expects(static::exactly(2))
            ->method('isFeatureEnabled')
            ->with(...self::withConsecutive(
                [ConfigurationKeyEnum::SHARE],
                [ConfigurationKeyEnum::DEMO_MODE],
            ))
            ->willReturn(true, false);
        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showConfirmation')
            ->with(
                'No Problem',
                'Expired shares have been cleaned',
                sprintf(
                    '%s/stats.php?action=share',
                    $webPath,
                )
            );
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
