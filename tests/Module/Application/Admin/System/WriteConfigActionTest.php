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
 *
 */

namespace Ampache\Module\Application\Admin\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\InstallationHelperInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

class WriteConfigActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private ?MockInterface $configContainer;

    /** @var InstallationHelperInterface|MockInterface|null */
    private ?MockInterface $installationHelper;

    /** @var ResponseFactoryInterface|MockInterface|null */
    private ?MockInterface $responseFactory;

    private ?WriteConfigAction $subject;

    protected function setUp(): void
    {
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->installationHelper = $this->mock(InstallationHelperInterface::class);
        $this->responseFactory    = $this->mock(ResponseFactoryInterface::class);

        $this->subject = new WriteConfigAction(
            $this->configContainer,
            $this->installationHelper,
            $this->responseFactory
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfDemoMode(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunWritesConfigAndReturnsSuccessResponse(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);

        $web_path = 'some-web-path';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $this->installationHelper->shouldReceive('write_config')
            ->with(
                Mockery::on(static fn (): bool => true)
            )
            ->once()
            ->andReturnTrue();

        $this->responseFactory->shouldReceive('createResponse')
            ->with(StatusCode::FOUND)
            ->once()
            ->andReturn($response);

        $response->shouldReceive('withHeader')
            ->with(
                'Location',
                sprintf('%s/index.php', $web_path)
            )
            ->once()
            ->andReturnSelf();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($web_path);

        $this->assertSame(
            $response,
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunWritesConfigAndReturnsFailureResponse(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);

        $web_path = 'some-web-path';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $this->installationHelper->shouldReceive('write_config')
            ->with(
                Mockery::on(function ($path): bool {
                    $test_path = __DIR__ . '/../../../../../config/ampache.cfg.php';

                    $this->assertTrue(
                        file_exists(dirname($path))
                    );

                    return realpath($test_path) === realpath($path);
                })
            );

        $this->responseFactory->shouldReceive('createResponse')
            ->with(StatusCode::FOUND)
            ->once()
            ->andReturn($response);

        $response->shouldReceive('withHeader')
            ->with(
                'Location',
                sprintf('%s/error.php?permission=%s', $web_path, 'config%2Fampache.cfg.php')
            )
            ->once()
            ->andReturnSelf();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($web_path);

        $this->assertSame(
            $response,
            $this->subject->run($request, $gatekeeper)
        );
    }
}
