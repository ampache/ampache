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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Lib\ArtItemRetrieverInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teapot\StatusCode;

class GetArtMethodTest extends MockeryTestCase
{
    /** @var ArtItemRetrieverInterface|MockInterface|null */
    private MockInterface $artItemRetriever;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private GetArtMethod $subject;

    public function setUp(): void
    {
        $this->artItemRetriever = $this->mock(ArtItemRetrieverInterface::class);
        $this->streamFactory    = $this->mock(StreamFactoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);

        $this->subject = new GetArtMethod(
            $this->artItemRetriever,
            $this->streamFactory,
            $this->configContainer
        );
    }

    public function testHandleBadRequestIfIdParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::BAD_REQUEST)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                []
            )
        );
    }

    public function testHandleReturnsBadRequestIfTypeParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::BAD_REQUEST)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['id' => 666]
            )
        );
    }

    public function testHandleReturnsBadRequestIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::BAD_REQUEST)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'id' => 666,
                    'type' => 'foobar'
                ]
            )
        );
    }

    public function testHandleReturnsNotFoundIfNoArtItemWasFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $type     = 'song';
        $objectId = 666;

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
            ->once()
            ->andReturnSelf();

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->artItemRetriever->shouldReceive('retrieve')
            ->with($user, $type, $objectId)
            ->once()
            ->andReturnNull();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'id' => $objectId,
                    'type' => $type
                ]
            )
        );
    }

    public function testHandleReturnsImage(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $art        = $this->mock(Art::class);
        $stream     = $this->mock(StreamInterface::class);

        $type          = 'song';
        $objectId      = 666;
        $image         = 'some-image';
        $imageMimeType = 'some-mime-type';

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->artItemRetriever->shouldReceive('retrieve')
            ->with($user, $type, $objectId)
            ->once()
            ->andReturn($art);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $art->raw      = $image;
        $art->raw_mime = $imageMimeType;

        $response->shouldReceive('withHeader')
            ->with('Content-Type', $imageMimeType)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Length', strlen($image))
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Access-Control-Allow-Origin', '*')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->streamFactory->shouldReceive('createStream')
            ->with($image)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'id' => $objectId,
                    'type' => $type
                ]
            )
        );
    }

    public function testHandleReturnsResizedImage(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $art        = $this->mock(Art::class);
        $stream     = $this->mock(StreamInterface::class);

        $size          = 42;
        $type          = 'song';
        $objectId      = 666;
        $image         = 'some-image';
        $imageMimeType = 'some-mime-type';

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->artItemRetriever->shouldReceive('retrieve')
            ->with($user, $type, $objectId)
            ->once()
            ->andReturn($art);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $art->shouldReceive('get_thumb')
            ->with([
                'width' => $size,
                'height' => $size
            ])
            ->once()
            ->andReturn([
                'thumb_mime' => $imageMimeType,
                'thumb' => $image,
            ]);

        $art->raw      = $image;
        $art->raw_mime = $imageMimeType;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::RESIZE_IMAGES)
            ->once()
            ->andReturnTrue();

        $response->shouldReceive('withHeader')
            ->with('Content-Type', $imageMimeType)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Content-Length', strlen($image))
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Access-Control-Allow-Origin', '*')
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->streamFactory->shouldReceive('createStream')
            ->with($image)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'id' => (string) $objectId,
                    'type' => $type,
                    'size' => (string) $size,
                ]
            )
        );
    }
}
