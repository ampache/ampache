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

use Ampache\Repository\VideoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class VideoTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private VideoRepositoryInterface&MockObject $videoRepository;

    public function testUpdateKeepsTheCurrentTitleWhenNoneIsSupplied(): void
    {
        $subject = new Video();

        $subject->id    = 666;
        $subject->title = 'old-title';

        $this->videoRepository->expects(static::once())
            ->method('update');

        $subject->update([]);

        static::assertSame('old-title', $subject->title);
    }

    public function testUpdateNullsAnUnparseableReleaseDate(): void
    {
        $subject = new Video();

        $subject->id           = 666;
        $subject->release_date = 1234;

        $this->videoRepository->expects(static::once())
            ->method('update')
            ->with($subject, true);

        $subject->update(['title' => 'some-title', 'release_date' => 'not-a-date']);

        static::assertNull($subject->release_date);
    }

    public function testUpdateParsesTheReleaseDateIntoATimestamp(): void
    {
        $subject = new Video();

        $subject->id = 666;

        $this->videoRepository->expects(static::once())
            ->method('update')
            ->with($subject, true);

        $subject->update(['title' => 'some-title', 'release_date' => '2015-01-01']);

        static::assertSame(strtotime('2015-01-01'), $subject->release_date);
    }

    public function testUpdateUtimeStampsTheVideo(): void
    {
        $this->videoRepository->expects(static::once())
            ->method('setUpdateTime')
            ->with(666, 1234);

        Video::update_utime(666, 1234);
    }

    public function testUpdateVideoCountsDelegatesForASavedVideo(): void
    {
        $this->videoRepository->expects(static::once())
            ->method('updateCounts')
            ->with(666);

        Video::update_video_counts(666);
    }

    public function testUpdateVideoCountsSkipsAnUnsavedVideo(): void
    {
        $this->videoRepository->expects(static::never())
            ->method('updateCounts');

        Video::update_video_counts(0);
    }

    public function testUpdateWritesTheTitleOnlyWhenNoReleaseDateIsGiven(): void
    {
        $subject = new Video();

        $subject->id    = 666;
        $subject->title = 'old-title';

        $this->videoRepository->expects(static::once())
            ->method('update')
            ->with($subject, false);

        static::assertSame(666, $subject->update(['title' => 'some-title']));

        static::assertSame('some-title', $subject->title);
    }

    protected function setUp(): void
    {
        $this->videoRepository = $this->createMock(VideoRepositoryInterface::class);
        $this->dic             = $this->createMock(ContainerInterface::class);

        $this->dic->method('get')
            ->with(VideoRepositoryInterface::class)
            ->willReturn($this->videoRepository);

        // the model reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;
    }
}
