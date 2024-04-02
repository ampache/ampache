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
 *
 */

namespace Ampache\Module\Util\Rss;

use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\Rss\Type\FeedTypeInterface;
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
use Psr\Container\ContainerInterface;

final readonly class RssFeedTypeFactory implements RssFeedTypeFactoryInterface
{
    public function __construct(
        private ContainerInterface $dic,
    ) {
    }

    public function createLibraryItemFeed(
        User $user,
        playable_item $libraryItem
    ): FeedTypeInterface {
        return new LibraryItemFeed(
            $this->dic->get(ModelFactoryInterface::class),
            $this->dic->get(LibraryItemLoaderInterface::class),
            $user,
            $libraryItem
        );
    }

    public function createRecentlyPlayedFeed(
        User $user
    ): FeedTypeInterface {
        return new RecentlyPlayedFeed(
            $user
        );
    }

    public function createNowPlayingFeed(): FeedTypeInterface
    {
        return new NowPlayingFeed();
    }

    public function createLatestAlbumFeed(
        User $user
    ): FeedTypeInterface {
        return new LatestAlbumFeed(
            $user
        );
    }

    public function createLatestArtistFeed(
        User $user
    ): FeedTypeInterface {
        return new LatestArtistFeed(
            $user,
        );
    }

    public function createLatestShoutFeed(): FeedTypeInterface
    {
        return new LatestShoutFeed(
            $this->dic->get(ShoutRepositoryInterface::class),
            $this->dic->get(ShoutObjectLoaderInterface::class),
        );
    }
}
