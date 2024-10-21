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

namespace Ampache\Module\Util\Rss\Surrogate;

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\playable_item;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Generator;

/**
 * Abstraction layer for creating rss/podcasts from playable-items
 */
final readonly class PlayableItemRssItemAdapter implements RssItemInterface
{
    public function __construct(
        private LibraryItemLoaderInterface $libraryItemLoader,
        private ModelFactoryInterface $modelFactory,
        private playable_item $playable,
        private ?User $user
    ) {
    }

    /**
     * Returns the item title
     */
    public function getTitle(): string
    {
        return sprintf('%s Podcast', $this->playable->get_fullname());
    }

    /**
     * Returns `true` if the item provides an image
     */
    public function hasImage(): bool
    {
        return $this->playable->has_art();
    }

    /**
     * Returns the items image-url
     */
    public function getImageUrl(): string
    {
        return (string)Art::url($this->playable->getId(), 'album');
    }

    /**
     * Returns `true` if the item provides a summary/description text
     */
    public function hasSummary(): bool
    {
        return $this->playable->get_description() !== '';
    }

    /**
     * Returns the items summary/description text
     */
    public function getSummary(): string
    {
        return $this->playable->get_description();
    }

    /**
     * Returns `true` if an item-owner is set
     */
    public function hasOwner(): bool
    {
        return ($this->playable->get_user_owner() ?? 0) > 0;
    }

    /**
     * Returns the name of the owner
     */
    public function getOwnerName(): string
    {
        $user = $this->modelFactory->createUser(
            (int) $this->playable->get_user_owner()
        );

        return (string) $user->get_fullname();
    }

    /**
     * Returns all media-items which are associated with the item
     *
     * @return Generator<array{
     *  title: string,
     *  guid: string,
     *  length: string,
     *  author: null|string,
     *  pubDate: null|string,
     *  type: null|string,
     *  size: null|string,
     *  url: null|string
     * }>
     */
    public function getMedias(): Generator
    {
        foreach ($this->playable->get_medias() as $media_info) {
            $media = $this->libraryItemLoader->load(
                $media_info['object_type'],
                $media_info['object_id'],
                [Song::class, Podcast_Episode::class]
            );

            if ($media === null) {
                continue;
            }
            /** @var Song|Podcast_Episode $media */
            $media->format();

            $data = [
                'title' => (string) $media->get_fullname(),
                'guid' => $media->get_link(),
                'length' => $media->f_time,
                'author' => $media->get_artist_fullname(),
                'pubDate' => null,
                'type' => null,
                'size' => null,
                'url' => null,
            ];

            if ($media->addition_time > 0) {
                $data['pubDate'] = date("r", $media->addition_time);
            }
            if ($media->mime) {
                $data['type'] = $media->mime;
                $data['size'] = (string) $media->size;
                if ($this->user !== null) {
                    $data['url']  = $media->play_url('', 'api', false, $this->user->getId(), $this->user->streamtoken);
                }
            }

            yield $data;
        }
    }
}
