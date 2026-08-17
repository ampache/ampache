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
 */

namespace Ampache\Repository\Model;

use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PodcastEpisodeTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;

    public function testUpdateAppliesTheDataAndPersists(): void
    {
        $subject = new Podcast_Episode();

        $subject->id = 666;

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('update')
            ->with($subject);

        self::assertSame(
            666,
            $subject->update([
                'title' => 'some-title',
                'website' => 'https%3A%2F%2Fsome-site',
                'description' => 'some-description',
                'author' => 'some-author',
                'category' => 'some-category',
            ])
        );

        self::assertSame('some-title', $subject->title);
        self::assertSame('https://some-site', $subject->website);
        self::assertSame('some-description', $subject->description);
        self::assertSame('some-author', $subject->author);
        self::assertSame('some-category', $subject->category);
    }

    public function testUpdateDiscardsAnInvalidWebsiteAndKeepsTheCurrentTitle(): void
    {
        $subject = new Podcast_Episode();

        $subject->id    = 666;
        $subject->title = 'old-title';

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('update');

        $subject->update(['website' => 'not-a-url']);

        self::assertSame('old-title', $subject->title);
        self::assertNull($subject->website);
    }

    public function testUpdateTruncatesTheOverlongDescriptionAndAuthor(): void
    {
        $subject = new Podcast_Episode();

        $subject->id = 666;

        $this->podcastEpisodeRepository->expects(static::once())
            ->method('update');

        $subject->update([
            'description' => str_repeat('a', 5000),
            'author' => str_repeat('b', 100),
        ]);

        self::assertSame(4096, strlen((string) $subject->description));
        self::assertSame(64, strlen((string) $subject->author));
    }

    public function testUpdateUtimeStampsTheEpisode(): void
    {
        $this->podcastEpisodeRepository->expects(static::once())
            ->method('setUpdateTime')
            ->with(666, 1234);

        Podcast_Episode::update_utime(666, 1234);
    }

    protected function setUp(): void
    {
        $this->podcastEpisodeRepository = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->dic                      = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(PodcastEpisodeRepositoryInterface::class)
            ->willReturn($this->podcastEpisodeRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
