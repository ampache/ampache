<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Util\Rss\Type\FeedTypeInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

interface RssFeedTypeFactoryInterface
{
    /**
     * Creates the feed related to a certain library-item
     */
    public function createLibraryItemFeed(?User $user, playable_item $libraryItem): FeedTypeInterface;

    /**
     * Creates a feed for recently played items
     */
    public function createRecentlyPlayedFeed(?User $user): FeedTypeInterface;

    /**
     * Creates a feed for currently playing items
     */
    public function createNowPlayingFeed(): FeedTypeInterface;

    /**
     * Creates a feed for recent albums
     */
    public function createLatestAlbumFeed(?User $user, ServerRequestInterface $request): FeedTypeInterface;

    /**
     * Creates a feed for recent artists
     */
    public function createLatestArtistFeed(?User $user, ServerRequestInterface $request): FeedTypeInterface;

    /**
     * Creates a feed for recent shouts
     */
    public function createLatestShoutFeed(): FeedTypeInterface;

    /**
     * Creates a feed for recent songs
     */
    public function createLatestSongFeed(?User $user, ServerRequestInterface $request): FeedTypeInterface;
}
