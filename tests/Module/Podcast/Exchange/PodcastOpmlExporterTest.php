<?php

declare(strict_types=1);

/**
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
 */

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use ArrayIterator;
use PhpTal\PHPTAL;
use PhpTal\PhpTalInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;
use Traversable;

class PodcastOpmlExporterTest extends TestCase
{
    use ConsecutiveParams;

    private TalFactoryInterface&MockObject $talFactory;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private PodcastOpmlExporter $subject;

    protected function setUp(): void
    {
        $this->talFactory        = $this->createMock(TalFactoryInterface::class);
        $this->podcastRepository = $this->createMock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastOpmlExporter(
            $this->talFactory,
            $this->podcastRepository
        );
    }

    public function testExportExportsPodcasts(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $talPage = $this->createMock(PhpTalInterface::class);

        $result      = 'some-result';
        $title       = 'some-title';
        $feedUrl     = 'some-feed-url';
        $website     = 'some-website';
        $language    = 'some-language';
        $description = 'some-description';

        $this->podcastRepository->expects(static::once())
            ->method('findAll')
            ->willReturn(new ArrayIterator([$podcast]));

        $this->talFactory->expects(static::once())
            ->method('createPhpTal')
            ->willReturn($talPage);

        $talPage->expects(static::once())
            ->method('setTemplate')
            ->with((string) realpath(__DIR__ . '/../../../../resources/templates/podcast/export.opml'));
        $talPage->expects(static::once())
            ->method('setOutputMode')
            ->with(PHPTAL::XML);
        $talPage->expects(static::exactly(3))
            ->method('set')
            ->with(
                ...self::withConsecutive(
                    ['TITLE', 'Ampache podcast subscriptions'],
                    ['CREATION_DATE', static::isType('string')],
                    [
                        'PODCASTS',
                        static::callback(function (Traversable $value) use ($title, $feedUrl, $website, $language, $description): bool {
                            $item = current(iterator_to_array($value));

                            return $item === [
                                'title' => $title,
                                'feedUrl' => $feedUrl,
                                'website' => $website,
                                'language' => $language,
                                'description' => $description,
                            ];
                        })
                    ],
                )
            );
        $talPage->expects(static::once())
            ->method('execute')
            ->willReturn($result);

        $podcast->expects(static::once())
            ->method('getTitle')
            ->willReturn($title);
        $podcast->expects(static::once())
            ->method('getFeedUrl')
            ->willReturn($feedUrl);
        $podcast->expects(static::once())
            ->method('getWebsite')
            ->willReturn($website);
        $podcast->expects(static::once())
            ->method('getLanguage')
            ->willReturn($language);
        $podcast->expects(static::once())
            ->method('getDescription')
            ->willReturn($description);

        static::assertSame(
            $result,
            $this->subject->export()
        );
    }

    public function testGetContentTypeReturnsValue(): void
    {
        static::assertSame(
            'text/x-opml',
            $this->subject->getContentType()
        );
    }
}
