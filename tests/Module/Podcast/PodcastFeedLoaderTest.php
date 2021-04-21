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

namespace Ampache\Module\Podcast;

use Ampache\MockeryTestCase;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\ExternalResourceLoaderInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class PodcastFeedLoaderTest extends MockeryTestCase
{
    /** @var MockInterface|LoggerInterface */
    private MockInterface $logger;

    /** @var MockInterface|ExternalResourceLoaderInterface */
    private MockInterface $externalResourceLoader;

    private PodcastFeedLoader $subject;

    public function setUp(): void
    {
        $this->logger                 = $this->mock(LoggerInterface::class);
        $this->externalResourceLoader = $this->mock(ExternalResourceLoaderInterface::class);

        $this->subject = new PodcastFeedLoader(
            $this->logger,
            $this->externalResourceLoader
        );
    }

    public function testLoadThrowsExceptionIfNotLoadable(): void
    {
        $feedUrl = 'some-feed-url';

        $this->expectException(Exception\PodcastFeedLoadingException::class);

        $this->logger->shouldReceive('info')
            ->with(
                sprintf('Syncing feed %s ...', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastFeedLoader::class]
            )
            ->once();
        $this->logger->shouldReceive('error')
            ->with(
                sprintf('Cannot access feed %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastFeedLoader::class]
            )
            ->once();

        $this->externalResourceLoader->shouldReceive('retrieve')
            ->with($feedUrl)
            ->once()
            ->andReturnNull();

        $this->subject->load($feedUrl);
    }

    public function testLoadThrowsExceptionIfResultIsNotParsable(): void
    {
        $feedUrl = 'some-feed-url';
        $content = 'some-content';

        $response = $this->mock(ResponseInterface::class);

        $this->expectException(Exception\PodcastFeedLoadingException::class);

        $this->logger->shouldReceive('info')
            ->with(
                sprintf('Syncing feed %s ...', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastFeedLoader::class]
            )
            ->once();
        $this->logger->shouldReceive('critical')
            ->with(
                sprintf('Cannot read feed %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastFeedLoader::class]
            )
            ->once();

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($content);

        $this->externalResourceLoader->shouldReceive('retrieve')
            ->with($feedUrl)
            ->once()
            ->andReturn($response);

        $this->subject->load($feedUrl);
    }

    public function testLoadReturnsData(): void
    {
        $feedUrl = 'some-feed-url';
        $content = '<root>some-content</root>';

        $response = $this->mock(ResponseInterface::class);

        $this->logger->shouldReceive('info')
            ->with(
                sprintf('Syncing feed %s ...', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastFeedLoader::class]
            )
            ->once();

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($content);

        $this->externalResourceLoader->shouldReceive('retrieve')
            ->with($feedUrl)
            ->once()
            ->andReturn($response);

        $this->assertInstanceOf(
            \SimpleXMLElement::class,
            $this->subject->load($feedUrl)
        );
    }
}
