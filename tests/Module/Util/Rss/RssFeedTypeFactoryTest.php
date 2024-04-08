<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
 *
 */

namespace Ampache\Module\Util\Rss;

use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\Rss\Type\LatestAlbumFeed;
use Ampache\Module\Util\Rss\Type\LatestArtistFeed;
use Ampache\Module\Util\Rss\Type\LatestShoutFeed;
use Ampache\Module\Util\Rss\Type\LibraryItemFeed;
use Ampache\Module\Util\Rss\Type\NowPlayingFeed;
use Ampache\Module\Util\Rss\Type\RecentlyPlayedFeed;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class RssFeedTypeFactoryTest extends TestCase
{
    use ConsecutiveParams;

    private ContainerInterface&MockObject $dic;

    private RssFeedTypeFactory $subject;

    protected function setUp(): void
    {
        $this->dic = $this->createMock(ContainerInterface::class);

        $this->subject = new RssFeedTypeFactory(
            $this->dic,
        );
    }

    public function testCreateLibraryItemFeedReturnsInstance(): void
    {
        $this->dic->expects(static::exactly(2))
            ->method('get')
            ->with(...self::withConsecutive(
                [ModelFactoryInterface::class],
                [LibraryItemLoaderInterface::class]
            ))
            ->willReturn(
                $this->createMock(ModelFactoryInterface::class),
                $this->createMock(LibraryItemLoaderInterface::class),
            );

        static::assertInstanceOf(
            LibraryItemFeed::class,
            $this->subject->createLibraryItemFeed(
                $this->createMock(User::class),
                $this->createMock(playable_item::class)
            )
        );
    }

    public function testCreateRecentlyPlayedFeedReturnsItem(): void
    {
        static::assertInstanceOf(
            RecentlyPlayedFeed::class,
            $this->subject->createRecentlyPlayedFeed(
                $this->createMock(User::class)
            )
        );
    }

    public function testCreateNowPlayingFeedReturnsInstance(): void
    {
        static::assertInstanceOf(
            NowPlayingFeed::class,
            $this->subject->createNowPlayingFeed()
        );
    }

    public function testCreateLatestAlbumFeedReturnsInstance(): void
    {
        static::assertInstanceOf(
            LatestAlbumFeed::class,
            $this->subject->createLatestAlbumFeed(
                $this->createMock(User::class)
            )
        );
    }

    public function testCreateLatestArtistsFeedReturnsInstance(): void
    {
        static::assertInstanceOf(
            LatestArtistFeed::class,
            $this->subject->createLatestArtistFeed(
                $this->createMock(User::class)
            )
        );
    }

    public function testCreateLatestShoutFeed(): void
    {
        $this->dic->expects(static::exactly(2))
            ->method('get')
            ->with(...self::withConsecutive(
                [ShoutRepositoryInterface::class],
                [ShoutObjectLoaderInterface::class]
            ))
            ->willReturn(
                $this->createMock(ShoutRepositoryInterface::class),
                $this->createMock(ShoutObjectLoaderInterface::class),
            );
        static::assertInstanceOf(
            LatestShoutFeed::class,
            $this->subject->createLatestShoutFeed()
        );
    }
}
