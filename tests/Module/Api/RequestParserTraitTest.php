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
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;

class RequestParserTraitTest extends MockeryTestCase
{
    /** @var object&RequestParserTrait */
    private object $subject;

    public function testFormBodyReadsParsedBody(): void
    {
        $this->assertSame(
            ['action' => 'song', 'filter' => '54'],
            $this->subject->parseRequestBody(
                $this->createRequest('POST', 'application/x-www-form-urlencoded', ['action' => 'song', 'filter' => '54'])
            )
        );
    }

    public function testGetRequestReadsNoBody(): void
    {
        $this->assertSame([], $this->subject->parseRequestBody($this->createRequest('GET')));
    }

    public function testInvalidJsonBodyIsIgnored(): void
    {
        $this->assertSame(
            [],
            $this->subject->parseRequestBody($this->createRequest('POST', 'application/json', null, 'not-json'))
        );
    }

    public function testJsonBodyIsDecoded(): void
    {
        $this->assertSame(
            ['action' => 'song', 'filter' => '54'],
            $this->subject->parseRequestBody(
                $this->createRequest('POST', 'application/json; charset=utf-8', null, '{"action":"song","filter":"54"}')
            )
        );
    }

    protected function setUp(): void
    {
        $this->subject = new class {
            use RequestParserTrait {
                parseRequestBody as public;
            }
        };
    }

    /**
     * @param null|array<string, mixed> $parsedBody
     */
    private function createRequest(
        string $method,
        string $contentType = '',
        ?array $parsedBody = null,
        string $body = '',
    ): ServerRequestInterface {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn($method);
        $request->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn($contentType);
        $request->shouldReceive('getParsedBody')->andReturn($parsedBody);
        $request->shouldReceive('getBody')->andReturn(Stream::create($body));

        return $request;
    }
}
