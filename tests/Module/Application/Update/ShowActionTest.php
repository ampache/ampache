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

namespace Ampache\Module\Application\Update;

use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\System\UpdateViewAdapterInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Gui\TalViewInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var TalFactoryInterface|MockInterface|null */
    private ?MockInterface $talFactory;

    /** @var GuiFactoryInterface|MockInterface|null */
    private ?MockInterface $guiFactory;

    /** @var ResponseFactoryInterface|MockInterface|null */
    private ?MockInterface $responseFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private ?MockInterface $streamFactory;

    private ?ShowAction $subject;

    public function setUp(): void
    {
        $this->talFactory      = $this->mock(TalFactoryInterface::class);
        $this->guiFactory      = $this->mock(GuiFactoryInterface::class);
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->streamFactory   = $this->mock(StreamFactoryInterface::class);

        $this->subject = new ShowAction(
            $this->talFactory,
            $this->guiFactory,
            $this->responseFactory,
            $this->streamFactory
        );
    }

    public function testRunReturnsRenderedResponse(): void
    {
        $request           = $this->mock(ServerRequestInterface::class);
        $gatekeeper        = $this->mock(GuiGatekeeperInterface::class);
        $response          = $this->mock(ResponseInterface::class);
        $updateViewAdapter = $this->mock(UpdateViewAdapterInterface::class);
        $talView           = $this->mock(TalViewInterface::class);
        $stream            = $this->mock(StreamInterface::class);

        $output = 'some-output';

        $this->guiFactory->shouldReceive('createUpdateViewAdapter')
            ->withNoArgs()
            ->once()
            ->andReturn($updateViewAdapter);

        $this->talFactory->shouldReceive('createTalView->setTemplate')
            ->with('update.xhtml')
            ->once()
            ->andReturn($talView);

        $talView->shouldReceive('setContext')
            ->with('UPDATE', $updateViewAdapter)
            ->once()
            ->andReturnSelf();
        $talView->shouldReceive('render')
            ->withNoArgs()
            ->once()
            ->andReturn($output);

        $this->streamFactory->shouldReceive('createStream')
            ->with($output)
            ->once()
            ->andReturn($stream);

        $this->responseFactory->shouldReceive('createResponse->withBody')
            ->with($stream)
            ->once()
            ->andReturn($response);

        $this->assertSame(
            $response,
            $this->subject->run($request, $gatekeeper)
        );
    }
}
