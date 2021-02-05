<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\WebDav;

use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Server;

class WebDavFactoryTest extends MockeryTestCase
{
    /** @var AuthenticationManagerInterface|MockInterface|null */
    private $authenticationManager;

    /** @var WebDavFactory|null */
    private ?WebDavFactory $subject;

    public function setUp(): void
    {
        $this->authenticationManager = Mockery::mock(AuthenticationManagerInterface::class);

        $this->subject = new WebDavFactory(
            $this->authenticationManager
        );
    }

    /**
     * @dataProvider methodDataProvider
     */
    public function testFactoryMethods(string $method, string $expected_instance_name, array $params): void
    {
        static::assertInstanceOf(
            $expected_instance_name,
            call_user_func_array([$this->subject, $method], $params)
        );
    }

    public function methodDataProvider(): array
    {
        return [
            ['createWebDavAuth', WebDavAuth::class, []],
            ['createWebDavCatalog', WebDavCatalog::class, [666]],
            ['createServer', Server::class, [null]],
            ['createPlugin', Plugin::class, [null]]
        ];
    }
}
