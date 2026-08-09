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

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class PodcastOpmlExporterTest extends TestCase
{
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private PodcastOpmlExporter $subject;

    /**
     * A title carrying markup must not be able to close the attribute it is written into.
     */
    public function testExportEscapesPodcastValues(): void
    {
        $podcast = $this->createMock(Podcast::class);

        $this->podcastRepository->expects(static::once())
            ->method('findAll')
            ->willReturn(new ArrayIterator([$podcast]));

        $podcast->expects(static::once())->method('getTitle')->willReturn('a "quoted" & <tagged> title');
        $podcast->expects(static::once())->method('getFeedUrl')->willReturn('');
        $podcast->expects(static::once())->method('getWebsite')->willReturn('');
        $podcast->expects(static::once())->method('getLanguage')->willReturn('');
        $podcast->expects(static::once())->method('getDescription')->willReturn('');

        $result = $this->subject->export();

        self::assertStringNotContainsString('<tagged>', $result);
        self::assertSame(
            'a "quoted" & <tagged> title',
            (string) (new SimpleXMLElement($result))->body->outline[0]['text']
        );
    }

    public function testExportExportsPodcasts(): void
    {
        $podcast = $this->createMock(Podcast::class);

        $title       = 'some-title';
        $feedUrl     = 'some-feed-url';
        $website     = 'some-website';
        $language    = 'some-language';
        $description = 'some-description';

        $this->podcastRepository->expects(static::once())
            ->method('findAll')
            ->willReturn(new ArrayIterator([$podcast]));

        $podcast->expects(static::once())->method('getTitle')->willReturn($title);
        $podcast->expects(static::once())->method('getFeedUrl')->willReturn($feedUrl);
        $podcast->expects(static::once())->method('getWebsite')->willReturn($website);
        $podcast->expects(static::once())->method('getLanguage')->willReturn($language);
        $podcast->expects(static::once())->method('getDescription')->willReturn($description);

        $xml = new SimpleXMLElement($this->subject->export());

        self::assertSame('Ampache podcast subscriptions', (string) $xml->head->title);
        self::assertNotSame('', (string) $xml->head->dateCreated);

        $outline = $xml->body->outline[0];

        self::assertNotNull($outline);
        self::assertSame($title, (string) $outline['text']);
        self::assertSame($language, (string) $outline['language']);
        self::assertSame($description, (string) $outline['description']);
        self::assertSame($feedUrl, (string) $outline['xmlUrl']);
        self::assertSame($website, (string) $outline['htmlUrl']);
    }

    public function testGetContentTypeReturnsValue(): void
    {
        self::assertSame(
            'text/x-opml',
            $this->subject->getContentType()
        );
    }

    protected function setUp(): void
    {
        $this->podcastRepository = $this->createMock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastOpmlExporter(
            $this->podcastRepository
        );
    }
}
