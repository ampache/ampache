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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class ShowAddAdvancedActionTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private RequestParserInterface|MockInterface|null $requestParser;
    private ?ShowAddAdvancedAction $subject;
    private MockInterface|UiInterface|null $ui;

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnTrue();

        ob_start();

        try {
            $this->subject->run($request, $gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertNotSame('', $output);
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->requestParser   = $this->mock(RequestParserInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('some-web-path');

        $this->requestParser->shouldReceive('getFromRequest')->andReturn('');

        $this->subject = new ShowAddAdvancedAction(
            $this->ui,
            $this->configContainer,
            $this->requestParser
        );
    }
}
