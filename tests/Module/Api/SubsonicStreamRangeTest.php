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

namespace Ampache\Module\Api;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\OpenSubsonic\Handler\StreamingHandler as OpenSubsonicStreamingHandler;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\StreamingHandler;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Playback\StreamProxyInterface;
use ReflectionMethod;

/** An iOS client over HTTP/2 got the whole file back for a range request (#4574) */
class SubsonicStreamRangeTest extends MockeryTestCase
{
    public function testOpenSubsonicForwardsTheClientHeadersOfTheStreamProxy(): void
    {
        $streamProxy = $this->streamProxy();

        $this->followStream(new OpenSubsonicStreamingHandler(
            $this->mock(OpenSubsonicResponseHandlerInterface::class),
            $this->mock(OpenSubsonic_Json_Data::class),
            $this->mock(OpenSubsonic_Xml_Data::class),
            $streamProxy,
        ));
    }

    public function testSubsonicForwardsTheClientHeadersOfTheStreamProxy(): void
    {
        $streamProxy = $this->streamProxy();

        $this->followStream(new StreamingHandler(
            $this->mock(SubsonicResponseHandlerInterface::class),
            $this->mock(Subsonic_Json_Data::class),
            $this->mock(Subsonic_Xml_Data::class),
            $streamProxy,
        ));
    }

    private function followStream(object $handler): void
    {
        // the handler drops one output buffer before streaming, so it gets its own to drop
        ob_start();
        // nothing listens on the discard port, so curl gives up at once
        new ReflectionMethod($handler, 'follow_stream')->invoke($handler, 'http://127.0.0.1:9/');
    }

    private function streamProxy(): StreamProxyInterface
    {
        $streamProxy = $this->mock(StreamProxyInterface::class);
        $streamProxy->shouldReceive('getRequestHeaders')->once()->andReturn(['Range: bytes=0-1']);

        return $streamProxy;
    }
}
