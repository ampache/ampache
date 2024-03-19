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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Module\Util\Rss\RssPodcastBuilderInterface;
use Ampache\Module\Util\Rss\Surrogate\PlayableItemRssItemAdapter;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;

final readonly class LibraryItemFeed implements FeedTypeInterface
{
    /**
     * @param array{object_type: string, object_id: int}|null $params
     */
    public function __construct(
        private RssPodcastBuilderInterface $rssPodcastBuilder,
        private ModelFactoryInterface $modelFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
        private User $user,
        private ?array $params
    ) {
    }

    public function handle(): string
    {
        if ($this->params === null) {
            return '';
        }

        $item = $this->libraryItemLoader->load(
            LibraryItemEnum::from($this->params['object_type']),
            $this->params['object_id'],
            [Album::class, Artist::class, Podcast::class]
        );

        if ($item !== null) {
            return $this->rssPodcastBuilder->build(
                new PlayableItemRssItemAdapter(
                    $this->libraryItemLoader,
                    $this->modelFactory,
                    $item,
                    $this->user
                ),
                $this->user
            );
        }

        return '';
    }
}
