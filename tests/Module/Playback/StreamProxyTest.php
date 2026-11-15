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

namespace Ampache\Module\Playback;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Util\UrlValidatorInterface;
use Psr\Log\LoggerInterface;

class StreamProxyTest extends MockeryTestCase
{
    /** @var array<string, mixed> */
    private array $savedServer = [];

    /** An iOS client over HTTP/2 got the whole file back for a range request (#4574) */
    public function testGetRequestHeadersForwardsTheClientRange(): void
    {
        $_SERVER['HTTP_RANGE']      = 'bytes=0-1';
        $_SERVER['HTTP_USER_AGENT'] = 'AppleCoreMedia/1.0.0';

        $headers = $this->subject()->getRequestHeaders();

        self::assertContains('Range: bytes=0-1', $headers);
        self::assertContains('User-Agent: AppleCoreMedia/1.0.0', $headers);
    }

    public function testGetRequestHeadersSendsNoRangeForAWholeFileRequest(): void
    {
        unset($_SERVER['HTTP_RANGE']);

        self::assertSame([], preg_grep('/^Range:/', $this->subject()->getRequestHeaders()));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;

        parent::tearDown();
    }

    private function subject(): StreamProxy
    {
        return new StreamProxy(
            $this->mock(LoggerInterface::class),
            $this->mock(UrlValidatorInterface::class),
            $this->mock(ConfigContainerInterface::class),
        );
    }
}
