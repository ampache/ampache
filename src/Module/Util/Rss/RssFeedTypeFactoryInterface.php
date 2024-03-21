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

use Ampache\Module\Util\Rss\Type\FeedTypeInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\User;

interface RssFeedTypeFactoryInterface
{
    public function createLibraryItemFeed(User $user, playable_item $libraryItem): FeedTypeInterface;

    public function createRecentlyPlayedFeed(User $user): FeedTypeInterface;

    public function createNowPlayingFeed(): FeedTypeInterface;

    public function createLatestAlbumFeed(User $user): FeedTypeInterface;

    public function createLatestArtistFeed(User $user): FeedTypeInterface;

    public function createLatestShoutFeed(): FeedTypeInterface;
}
