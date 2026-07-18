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

namespace Ampache\Module\Podcast\Feed;

use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Util\WebFetcher\Exception\FetchFailedException;
use Ampache\Module\Util\WebFetcher\WebFetcherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeedLoaderTest extends TestCase
{
    private FeedLoader $subject;
    private WebFetcherInterface&MockObject $webFetcher;

    public function testLoadFallsBackToEscapingBareAmpersands(): void
    {
        $feedUrl = 'https://example.com/feed.xml';

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
              <channel>
                <title>Some & Broken Title</title>
                <link>https://example.com</link>
                <description>desc</description>
                <language>en-us</language>
                <copyright>copyright</copyright>
                <generator>generator</generator>
              </channel>
            </rss>
            XML;

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willReturn($xml);

        $previousSetting = libxml_use_internal_errors(true);

        try {
            $result = $this->subject->load($feedUrl);
        } finally {
            libxml_use_internal_errors($previousSetting);
        }

        static::assertSame('Some & Broken Title', $result['title']);
        static::assertNull($result['artUrl']);
        static::assertNull($result['lastBuildDate']);
    }

    public function testLoadParsesFeedContent(): void
    {
        $feedUrl = 'https://example.com/feed.xml';

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
              <channel>
                <title>Some &amp; Podcast</title>
                <link>https://example.com</link>
                <description>Some &amp; description</description>
                <language>en-us</language>
                <copyright>Some &amp; copyright</copyright>
                <generator>Some generator</generator>
                <lastBuildDate>Tue, 01 Jul 2025 12:00:00 +0000</lastBuildDate>
                <image>
                  <url>https://example.com/art.jpg</url>
                </image>
                <item>
                  <title>Episode 1</title>
                </item>
              </channel>
            </rss>
            XML;

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willReturn($xml);

        $result = $this->subject->load($feedUrl);

        static::assertSame('Some & Podcast', $result['title']);
        static::assertSame('https://example.com', $result['website']);
        static::assertSame('Some & description', $result['description']);
        static::assertSame('en-us', $result['language']);
        static::assertSame('Some & copyright', $result['copyright']);
        static::assertSame('Some generator', $result['generator']);
        static::assertSame('https://example.com/art.jpg', $result['artUrl']);
        static::assertNotNull($result['lastBuildDate']);
        static::assertCount(1, $result['episodes']);
    }

    public function testLoadThrowsExceptionOnFetchFailure(): void
    {
        $feedUrl = 'https://example.com/feed.xml';

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willThrowException(new FetchFailedException());

        $this->expectException(FeedLoadingException::class);

        $this->subject->load($feedUrl);
    }

    public function testLoadThrowsExceptionOnUnparsableXml(): void
    {
        $feedUrl = 'https://example.com/feed.xml';

        $this->webFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedUrl)
            ->willReturn('not-xml-at-all');

        $this->expectException(FeedLoadingException::class);

        $previousSetting = libxml_use_internal_errors(true);

        try {
            $this->subject->load($feedUrl);
        } finally {
            libxml_use_internal_errors($previousSetting);
        }
    }

    protected function setUp(): void
    {
        $this->webFetcher = $this->createMock(WebFetcherInterface::class);

        $this->subject = new FeedLoader($this->webFetcher);
    }
}
