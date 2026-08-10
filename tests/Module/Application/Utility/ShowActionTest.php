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

namespace Ampache\Module\Application\Utility;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Mockery\MockInterface;
use Nyholm\Psr7\Response;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

class ShowActionTest extends MockeryTestCase
{
    private MockInterface|ResponseFactoryInterface|null $responseFactory;
    private ?ShowAction $subject;

    public function testRunAnswersWithADocumentWhenThereIsNoTarget(): void
    {
        $_SESSION = [];

        $result = $this->subject->run(
            $this->mock(ServerRequestInterface::class),
            $this->mock(GuiGatekeeperInterface::class)
        );

        $body = (string) $result->getBody();

        $this->assertSame(200, $result->getStatusCode());
        $this->assertFalse($result->hasHeader('Location'));
        // an empty body would make the hidden util iframe a quirks-mode document
        $this->assertStringStartsWith('<!DOCTYPE html>', $body);
    }

    public function testRunRedirectsToTheIframeTargetAndConsumesIt(): void
    {
        $_SESSION['iframe']['target'] = 'some-target';

        $result = $this->subject->run(
            $this->mock(ServerRequestInterface::class),
            $this->mock(GuiGatekeeperInterface::class)
        );

        $this->assertSame(RFC7231::FOUND, $result->getStatusCode());
        $this->assertSame('some-target', $result->getHeaderLine('Location'));
        $this->assertArrayNotHasKey('target', $_SESSION['iframe']);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);

        $this->responseFactory->shouldReceive('createResponse')
            ->andReturnUsing(static fn(): Response => new Response());

        $this->subject = new ShowAction($this->responseFactory);
    }
}
