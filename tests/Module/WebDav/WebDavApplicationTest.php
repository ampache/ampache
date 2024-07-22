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
 *
 */

namespace Ampache\Module\WebDav;

use Ampache\Config\ConfigContainerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\ICollection;
use Sabre\DAV\Server;

class WebDavApplicationTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface|null */
    private ?MockInterface $configContainer;

    /** @var MockInterface|WebDavFactoryInterface|null */
    private ?MockInterface $webDavFactory;

    private ?WebDavApplication $subject;

    protected function setUp(): void
    {
        $this->configContainer = Mockery::mock(ConfigContainerInterface::class);
        $this->webDavFactory   = Mockery::mock(WebDavFactoryInterface::class);

        $this->subject = new WebDavApplication(
            $this->configContainer,
            $this->webDavFactory
        );
    }

    public function testRunsDoesnWorkIfDisabled(): void
    {
        $this->configContainer->shouldReceive('isWebDavBackendEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->expectOutputString('Disabled');

        $this->subject->run();
    }

    public function testRunDelegatesToDavServer(): void
    {
        $catalog = Mockery::mock(ICollection::class);
        $server  = Mockery::mock(Server::class);
        $plugin  = Mockery::mock(Plugin::class);
        $auth    = Mockery::mock(BackendInterface::class);

        $raw_web_path = '/';

        $this->configContainer->shouldReceive('isWebDavBackendEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $this->configContainer->shouldReceive('getRawWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($raw_web_path);
        $this->configContainer->shouldReceive('isAuthenticationEnabled')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->webDavFactory->shouldReceive('createWebDavCatalog')
            ->withNoArgs()
            ->once()
            ->andReturn($catalog);
        $this->webDavFactory->shouldReceive('createServer')
            ->with($catalog)
            ->once()
            ->andReturn($server);
        $this->webDavFactory->shouldReceive('createWebDavAuth')
            ->withNoArgs()
            ->once()
            ->andReturn($auth);
        $this->webDavFactory->shouldReceive('createPlugin')
            ->with($auth)
            ->once()
            ->andReturn($plugin);

        $server->shouldReceive('setBaseUri')
            ->with('/webdav/index.php')
            ->once();
        $server->shouldReceive('addPlugin')
            ->with($plugin)
            ->once();
        $server->shouldReceive('exec')
            ->withNoArgs()
            ->once();

        $this->subject->run();
    }
}
