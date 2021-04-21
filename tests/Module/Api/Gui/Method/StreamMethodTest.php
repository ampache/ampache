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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode;

class StreamMethodTest extends MockeryTestCase
{
    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    private ?StreamMethod $subject;

    public function setUp(): void
    {
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);

        $this->subject = new StreamMethod(
            $this->modelFactory,
            $this->podcastEpisodeRepository
        );
    }

    public function testHandleReturns404IfParametersMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
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

    public function testHandleReturns404OnEmptyUrl(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast_Episode::class);

        $userId = 666;
        $songId = 42;

        $response->shouldReceive('withStatus')
            ->with(StatusCode::NOT_FOUND)
            ->once()
            ->andReturnSelf();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->podcastEpisodeRepository->shouldReceive('findById')
            ->with($songId)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('play_url')
            ->with(
                '&client=api',
                'api',
                function_exists('curl_version'),
                $userId
            )
            ->once()
            ->andReturn('');

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => 'podcast',
                    'id' => (string) $songId
                ]
            )
        );
    }

    public function testHandleReturnsUrl(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $userId  = 666;
        $songId  = 42;
        $url     = 'some-url:443/play';
        $length  = 1;
        $offset  = 33;
        $bitrate = 21;
        $format  = 'mp3';

        $response->shouldReceive('withStatus')
            ->with(StatusCode::FOUND)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with(
                'Location',
                str_replace(':443/play', '/play', $url)
            )
            ->once()
            ->andReturnSelf();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with(
                sprintf(
                    '&client=api&content_length=required&transcode_to=%s&bitrate=%d&frame=%d',
                    $format,
                    $bitrate,
                    $offset
                ),
                'api',
                function_exists('curl_version'),
                $userId
            )
            ->once()
            ->andReturn($url);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => 'song',
                    'id' => (string) $songId,
                    'bitrate' => $bitrate,
                    'format' => $format,
                    'offset' => $offset,
                    'length' => $length
                ]
            )
        );
    }
}
