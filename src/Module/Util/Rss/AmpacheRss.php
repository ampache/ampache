<?php

declare(strict_types=0);

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
use Ampache\Module\Util\Rss\Type\LatestAlbumFeed;
use Ampache\Module\Util\Rss\Type\LatestArtistFeed;
use Ampache\Module\Util\Rss\Type\LatestShoutFeed;
use Ampache\Module\Util\Rss\Type\LibraryItemFeed;
use Ampache\Module\Util\Rss\Type\NowPlayingFeed;
use Ampache\Module\Util\Rss\Type\RecentlyPlayedFeed;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;

final class AmpacheRss implements AmpacheRssInterface
{
    public function __construct(
        private readonly ShoutRepositoryInterface $shoutRepository,
        private readonly ShoutObjectLoaderInterface $shoutObjectLoader,
        private readonly RssPodcastBuilderInterface $rssPodcastBuilder,
        private readonly ModelFactoryInterface $modelFactory,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
    ) {
    }

    /**
     * get_xml
     * This returns the xmldocument for the current rss type, it calls a sub function that gathers the data
     * and then uses the xmlDATA class to build the document
     *
     * @param null|array{object_type: string, object_id: int} $params
     */
    public function get_xml(
        User $user,
        RssFeedTypeEnum $type,
        ?array $params = null
    ): string {
        $functions = [
            RssFeedTypeEnum::LIBRARY_ITEM->value => function () use ($user, $params): LibraryItemFeed {
                return new LibraryItemFeed(
                    $this->rssPodcastBuilder,
                    $this->modelFactory,
                    $this->libraryItemLoader,
                    $user,
                    $params
                );
            },
            RssFeedTypeEnum::NOW_PLAYING->value => function (): NowPlayingFeed {
                return new NowPlayingFeed();
            },
            RssFeedTypeEnum::RECENTLY_PLAYED->value => function () use ($user): RecentlyPlayedFeed {
                return new RecentlyPlayedFeed($user->getId());
            },
            RssFeedTypeEnum::LATEST_ALBUM->value => function () use ($user): LatestAlbumFeed {
                return new LatestAlbumFeed(
                    $user
                );
            },
            RssFeedTypeEnum::LATEST_ARTIST->value => function () use ($user): LatestArtistFeed {
                return new LatestArtistFeed(
                    $user
                );
            },
            RssFeedTypeEnum::LATEST_SHOUT->value => function (): LatestShoutFeed {
                return new LatestShoutFeed(
                    $this->shoutRepository,
                    $this->shoutObjectLoader
                );
            },
        ];

        return $functions[$type->value]()->handle();
    }
}
