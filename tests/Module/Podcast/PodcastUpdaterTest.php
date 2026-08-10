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

namespace Ampache\Module\Podcast;

use Ampache\Module\Podcast\Feed\Exception\FeedLoadingException;
use Ampache\Module\Podcast\Feed\FeedLoaderInterface;
use Ampache\Repository\Model\Podcast;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastUpdaterTest extends TestCase
{
    private FeedLoaderInterface&MockObject $feedLoader;
    private LoggerInterface&MockObject $logger;
    private PodcastUpdater $subject;

    public function testUpdateAppliesTheChannelValues(): void
    {
        $lastBuildDate = new DateTime();

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')
            ->willReturn('https://some-feed');

        $this->feedLoader->expects(static::once())
            ->method('load')
            ->with('https://some-feed')
            ->willReturn([
                'title' => 'some-title',
                'website' => 'some-website',
                'description' => 'some-description',
                'language' => 'en',
                'copyright' => 'some-copyright',
                'generator' => 'some-generator',
                'episodes' => null,
                'artUrl' => 'https://some-art',
                'lastBuildDate' => $lastBuildDate,
            ]);

        $podcast->expects(static::once())
            ->method('setTitle')
            ->with('some-title');
        $podcast->expects(static::once())
            ->method('setWebsite')
            ->with('some-website');
        $podcast->expects(static::once())
            ->method('setDescription')
            ->with('some-description');
        $podcast->expects(static::once())
            ->method('setLanguage')
            ->with('en');
        $podcast->expects(static::once())
            ->method('setCopyright')
            ->with('some-copyright');
        $podcast->expects(static::once())
            ->method('setGenerator')
            ->with('some-generator');
        $podcast->expects(static::once())
            ->method('setLastBuildDate')
            ->with($lastBuildDate);
        $podcast->expects(static::once())
            ->method('save');

        static::assertTrue(
            // art insertion needs the database, so it is excluded here
            $this->subject->update($podcast, false)
        );
    }

    public function testUpdateDoesNothingWithoutFeedUrl(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->expects(static::once())
            ->method('getFeedUrl')
            ->willReturn('');

        $this->feedLoader->expects(static::never())
            ->method('load');

        static::assertFalse(
            $this->subject->update($podcast)
        );
    }

    public function testUpdateKeepsValuesTheChannelDoesNotSupply(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')
            ->willReturn('https://some-feed');

        $this->feedLoader->expects(static::once())
            ->method('load')
            ->willReturn([
                'title' => 'some-title',
                'website' => '',
                'description' => '',
                'language' => '',
                'copyright' => '',
                'generator' => '',
                'episodes' => null,
                'artUrl' => null,
                'lastBuildDate' => null,
            ]);

        $podcast->expects(static::once())
            ->method('setTitle')
            ->with('some-title');
        $podcast->expects(static::never())
            ->method('setWebsite');
        $podcast->expects(static::never())
            ->method('setDescription');
        $podcast->expects(static::never())
            ->method('setLanguage');
        $podcast->expects(static::never())
            ->method('setCopyright');
        $podcast->expects(static::never())
            ->method('setGenerator');
        $podcast->expects(static::once())
            ->method('save');

        static::assertTrue(
            $this->subject->update($podcast, false)
        );
    }

    public function testUpdateRethrowsLoadingErrors(): void
    {
        static::expectException(FeedLoadingException::class);

        $podcast = $this->createMock(Podcast::class);
        $podcast->method('getFeedUrl')
            ->willReturn('https://some-feed');

        $this->feedLoader->expects(static::once())
            ->method('load')
            ->with('https://some-feed')
            ->willThrowException(new FeedLoadingException());

        $podcast->expects(static::never())
            ->method('save');

        $this->subject->update($podcast);
    }

    protected function setUp(): void
    {
        $this->feedLoader = $this->createMock(FeedLoaderInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->subject = new PodcastUpdater(
            $this->feedLoader,
            $this->logger,
        );
    }
}
