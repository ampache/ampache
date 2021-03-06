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

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\MockeryTestCase;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class MediaUrlListGeneratorTest extends MockeryTestCase
{
    /** @var ContainerInterface|MockInterface */
    private MockInterface $dic;

    /** @var ResponseFactoryInterface|MockInterface */
    private MockInterface $responseFactory;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    private MediaUrlListGenerator $subject;

    public function setUp(): void
    {
        $this->dic             = $this->mock(ContainerInterface::class);
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new MediaUrlListGenerator(
            $this->dic,
            $this->responseFactory,
            $this->logger
        );
    }

    public function testGenerateReturnsEmptyResponseIfUrlListIsEmpty(): void
    {
        $playlist = $this->mock(Stream_Playlist::class);
        $response = $this->mock(ResponseInterface::class);

        $type       = 'some-type';
        $playlistId = 666;

        $this->responseFactory->shouldReceive('createResponse')
            ->withNoArgs()
            ->once()
            ->andReturn($response);

        $playlist->urls = [];
        $playlist->id   = $playlistId;

        $this->logger->shouldReceive('error')
            ->with(
                sprintf('Error: Empty URL array for %d', $playlist->id),
                [LegacyLogger::CONTEXT_TYPE => MediaUrlListGenerator::class]
            )
            ->once();

        $this->assertSame(
            $response,
            $this->subject->generate($playlist, $type)
        );
    }

    /**
     * @dataProvider generatorTypeDataProvider
     */
    public function testGenerateReturnsGeneratorResult(
        string $type,
        string $className
    ): void {
        $playlist      = $this->mock(Stream_Playlist::class);
        $response      = $this->mock(ResponseInterface::class);
        $generatorType = $this->mock(MediaUrlListGeneratorTypeInterface::class);

        $playlistId = 666;

        $this->responseFactory->shouldReceive('createResponse')
            ->withNoArgs()
            ->once()
            ->andReturn($response);

        $playlist->urls = ['some-url'];
        $playlist->id   = $playlistId;

        $this->dic->shouldReceive('get')
            ->with($className)
            ->once()
            ->andReturn($generatorType);

        $generatorType->shouldReceive('generate')
            ->with($playlist, $response)
            ->once()
            ->andReturn($response);

        $this->logger->shouldReceive('info')
            ->with(
                sprintf('Generating a {%s} object...', $type),
                [LegacyLogger::CONTEXT_TYPE => MediaUrlListGenerator::class]
            )
            ->once();

        $this->assertSame(
            $response,
            $this->subject->generate($playlist, $type)
        );
    }

    public function generatorTypeDataProvider(): array
    {
        return [
            ['download', DownloadMediaUrlListGeneratorType::class],
            ['democratic', DemocraticMediaUrlListGeneratorType::class],
            ['localplay', LocalplayMediaUrlGeneratorType::class],
            ['web_player', WebPlayerMediaUrlListGeneratorType::class],
            ['asx', AsxMediaUrlListGeneratorType::class],
            ['pls', PlsMediaUrlListGeneratorType::class],
            ['simple_m3u', SimpleM3uMediaUrlListGeneratorType::class],
            ['xspf', XspfMediaUrlListGeneratorType::class],
            ['hls', HlsMediaUrlListGeneratorType::class],
            ['m3u', M3uMediaUrlListGeneratorType::class],
            ['foobar', M3uMediaUrlListGeneratorType::class],
        ];
    }
}
