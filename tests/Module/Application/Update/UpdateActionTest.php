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

namespace Ampache\Module\Application\Update;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Exception\QueryFailedException;
use Ampache\Module\System\Update\UpdaterInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\StatusCode\RFC\RFC7231;

class UpdateActionTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ResponseFactoryInterface|MockInterface|null $responseFactory;
    private ?UpdateAction $subject;
    private UpdaterInterface|MockInterface|null $updater;

    public function testRunRedirectsToTheTestPageIfTheDatabaseVersionIsUnreadable(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);

        $webPath = 'some-web-path';

        $this->updater->shouldReceive('hasPendingUpdates')
            ->withNoArgs()
            ->once()
            ->andThrow(new QueryFailedException());

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->responseFactory->shouldReceive('createResponse')
            ->with(RFC7231::FOUND)
            ->once()
            ->andReturn($response);

        $response->shouldReceive('withHeader')
            ->with('Location', $webPath . '/test.php')
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->run($request, $gatekeeper)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->updater         = $this->mock(UpdaterInterface::class);

        $this->subject = new UpdateAction(
            $this->mock(GuiFactoryInterface::class),
            $this->responseFactory,
            $this->configContainer,
            $this->mock(StreamFactoryInterface::class),
            $this->updater
        );
    }
}
