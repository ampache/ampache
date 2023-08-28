<?php
/*
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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\InstallationHelperInterface;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\UiInterface;
use Mockery;
use Mockery\MockInterface;
use org\bovigo\vfs\DirectoryIterationTestCase;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class GenerateConfigActionTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var Horde_Browser|MockInterface|null */
    private MockInterface $browser;

    /** @var InstallationHelperInterface|MockInterface|null */
    private MockInterface $installationHelper;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var ResponseFactoryInterface|MockInterface|null */
    private MockInterface $responseFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    private ?GenerateConfigAction $subject;

    public function setUp(): void
    {
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->browser            = $this->mock(Horde_Browser::class);
        $this->installationHelper = $this->mock(InstallationHelperInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);
        $this->responseFactory    = $this->mock(ResponseFactoryInterface::class);
        $this->streamFactory      = $this->mock(StreamFactoryInterface::class);

        $this->subject = new GenerateConfigAction(
            $this->configContainer,
            $this->browser,
            $this->installationHelper,
            $this->ui,
            $this->responseFactory,
            $this->streamFactory
        );
    }

    public function testRunThrowsAccessViolationIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsAccessViolationIfDemoMode(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunReturnsResponse(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $root       = vfsStream::setup('/');
        $configFile = vfsStream::newFile('config');

        $root->addChild($configFile);

        $headerValue       = 'some-header-key';
        $headerName        = 'some-header-name';
        $generatedConfig   = 'some-config';
        $configFilePath    = $configFile->url();
        $configFileContent = 'key=value';

        $configFile->setContent($configFileContent);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getConfigFilePath')
            ->withNoArgs()
            ->once()
            ->andReturn($configFilePath);

        $this->browser->shouldReceive('getDownloadHeaders')
            ->with(
                'ampache.cfg.php',
                'text/plain',
                false,
                strlen($generatedConfig)
            )
            ->once()
            ->andReturn([$headerName => $headerValue]);

        $this->responseFactory->shouldReceive('createResponse')
            ->withNoArgs()
            ->once()
            ->andReturn($response);

        $response->shouldReceive('withHeader')
            ->with($headerName, $headerValue)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->installationHelper->shouldReceive('generate_config')
            ->with(['key' => 'value'])
            ->once()
            ->andReturn($generatedConfig);

        $this->streamFactory->shouldReceive('createStream')
            ->with($generatedConfig)
            ->once()
            ->andReturn($stream);

        $this->subject->run($request, $gatekeeper);
    }
}
